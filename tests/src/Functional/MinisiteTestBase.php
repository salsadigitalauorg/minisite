<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Random;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\minisite\Asset;
use Drupal\minisite\LegacyWrapper;
use Drupal\minisite\Minisite;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\minisite\Traits\FixtureTrait;

/**
 * Provides methods specifically for testing Minisite module's field handling.
 */
abstract class MinisiteTestBase extends BrowserTestBase {

  use FixtureTrait;
  use FieldCreationTrait;
  use FieldUiTestTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'file',
    'field',
    'field_ui',
    'path',
    'minisite',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Content type used to create the field on.
   *
   * @var string
   */
  protected $contentType = 'article';

  /**
   * Array of admin user permissions.
   *
   * Can be overridden from descendant classes.
   *
   * @var array
   */
  protected $adminUserPermissions = [
    'access content',
    'access administration pages',
    'administer site configuration',
    'administer users',
    'administer permissions',
    'administer content types',
    'administer node fields',
    'administer node display',
    'administer nodes',
    'bypass node access',
    'administer url aliases',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fixtureSetUp();

    $this->adminUser = $this->drupalCreateUser($this->adminUserPermissions);
    $this->drupalLogin($this->adminUser);

    $this->drupalCreateContentType(['type' => $this->contentType, 'name' => 'Article']);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();

    $this->fixtureTearDown();
  }

  /**
   * Captures and saves a screenshot.
   *
   * The result of calling this function will be triggering fail in order to
   * output the URL to the generated screenshot. This is due to result printer
   * not allowing print output from within the test.
   *
   * @todo: Find a better way to add output to the result printer.
   */
  protected function screenshot() {
    $base_directory = '/sites/simpletest/browser_output';

    $directory = DRUPAL_ROOT . $base_directory;

    // Ensure directory exists.
    if (!is_dir($directory)) {
      mkdir($directory, 0777, TRUE);
    }

    $current_url = substr(Html::cleanCssIdentifier($this->getSession()->getCurrentUrl()), 100);

    $filename = uniqid() . '_' . $current_url . '.html';
    $full_filename = file_create_filename($filename, $directory);

    $screenshot = $this->getSession()->getPage()->getContent();
    file_put_contents($full_filename, $screenshot);

    $url = $GLOBALS['base_url'] . $base_directory . '/' . $filename;

    $this->fail($url);
  }

  /**
   * Uploads a file to a node.
   *
   * @param \Drupal\file\FileInterface $file
   *   The File to be uploaded.
   * @param string $field_name
   *   The name of the field on which the files should be saved.
   * @param int|string $nid_or_type
   *   A numeric node id to upload files to an existing node, or a string
   *   indicating the desired bundle for a new node.
   * @param bool $new_revision
   *   The revision number.
   * @param array $extras
   *   Additional values when a new node is created.
   *
   * @return int
   *   The node id.
   */
  public function uploadNodeFile(FileInterface $file, $field_name, $nid_or_type, $new_revision = TRUE, array $extras = []) {
    return $this->uploadNodeFiles([$file], $field_name, $nid_or_type, $new_revision, $extras);
  }

  /**
   * Uploads multiple files to a node.
   *
   * @param \Drupal\file\FileInterface[] $files
   *   The files to be uploaded.
   * @param string $field_name
   *   The name of the field on which the files should be saved.
   * @param int|string $nid_or_type
   *   A numeric node id to upload files to an existing node, or a string
   *   indicating the desired bundle for a new node.
   * @param bool $new_revision
   *   The revision number.
   * @param array $extras
   *   Additional values when a new node is created.
   *
   * @return int
   *   The node id.
   */
  public function uploadNodeFiles(array $files, $field_name, $nid_or_type, $new_revision = TRUE, array $extras = []) {
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'revision' => (string) (int) $new_revision,
    ];

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    if (is_numeric($nid_or_type)) {
      $nid = $nid_or_type;
      $node_storage->resetCache([$nid]);
      $node = $node_storage->load($nid);
    }
    else {
      // Add a new node.
      $extras['type'] = $nid_or_type;
      $node = $this->drupalCreateNode($extras);
      $nid = $node->id();
      // Save at least one revision to better simulate a real site.
      $node->setNewRevision();
      $node->save();
      $node_storage->resetCache([$nid]);
      $node = $node_storage->load($nid);
      $this->assertNotEqual($nid, $node->getRevisionId(), 'Node revision exists.');
    }
    $this->drupalGet("node/$nid/edit");
    $page = $this->getSession()->getPage();

    // Attach files to the node.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    // File input name depends on number of files already uploaded.
    $field_num = count($node->{$field_name});
    foreach ($files as $i => $file) {
      $delta = $field_num + $i;
      $file_path = $this->container->get('file_system')->realpath($file->getFileUri());
      $name = 'files[' . $field_name . '_' . $delta . ']';
      if ($field_storage->getCardinality() != 1) {
        $name .= '[]';
      }
      if (count($files) == 1) {
        $edit[$name] = $file_path;
      }
      else {
        $page->attachFileToField($name, $file_path);
        $this->drupalPostForm(NULL, [], $this->t('Upload'));
      }
    }

    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    return $nid;
  }

  /**
   * Asserts that a file exists physically on disk.
   *
   * Uses PHPUnit\Framework\Assert::assertFileUriExists() to work with
   * file entities.
   *
   * @param \Drupal\File\FileInterface|string $filename
   *   Either the file entity or the file URI.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  public static function assertFileUriExists($filename, $message = '') {
    $message = isset($message) ? $message : new FormattableMarkup('File %file exists on the disk.', ['%file' => $filename->getFileUri()]);
    $filename = $filename instanceof FileInterface ? $filename->getFileUri() : $filename;
    parent::assertFileExists($filename, $message);
  }

  /**
   * Asserts that a file exists in the database.
   */
  public function assertFileEntryExists($file, $message = NULL) {
    $this->container->get('entity_type.manager')->getStorage('file')->resetCache();
    $db_file = File::load($file->id());
    $message = isset($message) ? $message : new FormattableMarkup('File %file exists in database at the correct path.', ['%file' => $file->getFileUri()]);
    $this->assertEqual($db_file->getFileUri(), $file->getFileUri(), $message);
  }

  /**
   * Asserts that a file does not exist in the database.
   */
  public function assertFileEntryNotExists($file, $message = NULL) {
    $this->container->get('entity_type.manager')->getStorage('file')->resetCache();
    $db_file = File::load($file->id());
    $message = isset($message) ? $message : new FormattableMarkup('File %file does not exists in database at the correct path.', ['%file' => $file->getFileUri()]);
    $this->assertNull($db_file, $message);
  }

  /**
   * Convert file provided by absolute path to file entity.
   *
   * @param string $absolute_file_path
   *   Absolute path to file.
   *
   * @return \Drupal\file\Entity\File
   *   The File entity object.
   */
  protected function convertToFileEntity($absolute_file_path) {
    $archive_file = basename($absolute_file_path);

    $file = new \stdClass();
    $file->uri = $absolute_file_path;
    $file->filename = $archive_file;
    $file->name = pathinfo($archive_file, PATHINFO_FILENAME);
    // Add a filesize property to files as would be read by
    // \Drupal\file\Entity\File::load().
    $file->filesize = filesize($file->uri);

    return File::create((array) $file);
  }

  /**
   * Get valid test files stub.
   */
  public function getTestFilesStubValid() {
    return [
      'parent' . DIRECTORY_SEPARATOR . 'index.html' => $this->fixtureHtmlPage('Index page', $this->fixtureLink('Go to Page 1', 'page1.html')),
      'parent' . DIRECTORY_SEPARATOR . 'page1.html' => $this->fixtureHtmlPage('Page 1', $this->fixtureLink('Go to Page 2', 'page2.html')),
      'parent' . DIRECTORY_SEPARATOR . 'page2.html' => $this->fixtureHtmlPage('Page 2'),
      'parent' . DIRECTORY_SEPARATOR . 'image.jpg' => file_get_contents($this->getFixtureFileDir() . DIRECTORY_SEPARATOR . 'example.jpeg'),
    ];
  }

  /**
   * Shorthand to get a valid archive file.
   *
   * @return \Drupal\file\Entity\File
   *   The File entity object.
   */
  public function getTestArchiveValid() {
    // Create valid fixture archive.
    // All files must reside in the top-level directory, archive must contain
    // index.html file, and files should have allowed extension.
    $archive_file_absolute = $this->fixtureCreateArchive($this->getTestFilesStubValid());

    return $this->convertToFileEntity($archive_file_absolute);
  }

  /**
   * Shorthand to get an invalid archive file.
   *
   * @return \Drupal\file\Entity\File
   *   The File entity object.
   */
  public function getTestArchiveInvalidFormat() {
    $filename = $this->fixtureCreateFile('invalid.zip', rand(1, 9));

    return $this->convertToFileEntity($filename);
  }

  /**
   * Assert archive file exists.
   */
  public function assertArchiveFileExist(FileInterface $file) {
    $this->assertFileEntryExists($file, 'Archive file entry exists');
    $this->assertFileUriExists(Minisite::getCommonArchiveDir() . DIRECTORY_SEPARATOR . $file->getFilename(), 'Archive file exists');
  }

  /**
   * Assert archive file does not exist.
   */
  public function assertArchiveFileNotExist(FileInterface $file) {
    $this->assertFileEntryNotExists($file, 'Archive file entry does not');
    $this->assertFileNotExists(Minisite::getCommonArchiveDir() . DIRECTORY_SEPARATOR . $file->getFilename(), 'Archive file does not exist');
  }

  /**
   * Assert assets paths exist.
   */
  public function assertAssetFilesExist($files) {
    $actual_files = array_keys(LegacyWrapper::scanDirectory(Minisite::getCommonAssetDir(), '/.*/'));

    $this->assertEquals(count($actual_files), count($files));
    foreach ($files as $test_file) {
      $found_files = array_filter($actual_files, function ($value) use ($test_file) {
        return substr($value, -strlen($test_file)) === $test_file;
      });

      $this->assertTrue(count($found_files) == 1, 'Asset file found in the list of created files');
    }
  }

  /**
   * Assert assets paths not exist.
   */
  public function assertAssetFilesNotExist($files) {
    $actual_files = array_keys(LegacyWrapper::scanDirectory(Minisite::getCommonAssetDir(), '/.*/'));
    foreach ($files as $test_file) {
      $found_files = array_filter($actual_files, function ($value) use ($test_file) {
        return substr($value, -strlen($test_file)) === $test_file;
      });

      $this->assertTrue(empty($found_files), 'Asset file does not exist');
    }
  }

  /**
   * Create Minisite field through UI and upload a fixture archive.
   *
   * @param string $node_type
   *   Node type (bundle).
   * @param string $node_title
   *   Node title to set.
   * @param string $description
   *   (optional) Minisite field description to set.
   * @param array $edit
   *   (optional) Additional node form elements to set before the node is
   *   created.
   *
   * @return string
   *   Created field name.
   */
  public function createFieldAndNode($node_type, $node_title, $description = NULL, array $edit = []) {
    $field_name = 'ms_fn_' . strtolower($this->randomMachineName(4));
    $field_label = 'ms_fl_' . strtolower($this->randomMachineName(4));

    // Create field through UI.
    // Note that config schema is also validated when field is created.
    $storage_edit = ['settings[uri_scheme]' => 'public'];
    $this->fieldUIAddNewField("admin/structure/types/manage/$node_type", $field_name, $field_label, 'minisite', $storage_edit);

    // Create valid fixture archive.
    // All files must reside in the top-level directory and archive must contain
    // index.html file.
    $test_archive = $this->getTestArchiveValid();

    // Manually clear cache on the tester side.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create node and upload fixture file.
    // Note that in order to reveal field fields available only after file
    // is uploaded, we submitting a form with a file and without a title.
    $edit1 = [
      'files[field_' . $field_name . '_' . 0 . ']' => $test_archive->getFileUri(),
    ];
    $this->drupalPostForm("node/add/$node_type", $edit1, $this->t('Save'));
    $edit2 = [
      'title[0][value]' => $node_title,
      'field_' . $field_name . '[' . 0 . '][options][alias_status]' => TRUE,
    ];

    $edit = $edit2 + $edit;

    if (!empty($description)) {
      $edit['field_' . $field_name . '[' . 0 . '][description]'] = $description;
    }

    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    return $field_name;
  }

  /**
   * Assert that Minisite archive file was uploaded and assets expanded.
   */
  public function assertMinisiteUploaded($node, $field_name, $test_archive_assets) {
    $archive_file = $this->getUploadedArchiveFile($node, $field_name);
    $this->assertArchiveFileExist($archive_file);
    $this->assertAssetFilesExist($test_archive_assets);
  }

  /**
   * Assert that a Minisite archive and assets were removed.
   */
  public function assertMinisiteRemoved($node, $field_name, $test_archive_assets) {
    $archive_file = $this->getUploadedArchiveFile($node, $field_name);
    $this->assertArchiveFileNotExist($archive_file);
    $this->assertAssetFilesNotExist($test_archive_assets);
    // Assert that archive file has been removed.
    $this->assertFileEntryNotExists($archive_file);
    // Assert that there are no records in the 'minisites_assets' table about
    // assets for this node.
    foreach ($test_archive_assets as $test_archive_asset) {
      $this->assertNull(Asset::loadByUri($test_archive_asset));
    }
  }

  /**
   * Get uploaded archive file.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node object to get the file from.
   * @param string $field_name
   *   Field name without 'field_' prefix.
   *
   * @return \Drupal\file\Entity\File|null
   *   Uploaded file or NULL.
   */
  public function getUploadedArchiveFile(Node $node, $field_name) {
    return File::load($node->{'field_' . $field_name}->target_id);
  }

  /**
   * Helper to browse fixture pages.
   */
  public function browseFixtureMinisite($node, $description) {
    // Visit node and start browsing minisite.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertLink($description);
    $this->clickLink($description);

    // Brose minisite pages starting from index page.
    $this->assertText('Index page');
    $this->assertLink('Go to Page 1');
    $this->clickLink('Go to Page 1');

    $this->assertText('Page 1');
    $this->assertLink('Go to Page 2');
    $this->clickLink('Go to Page 2');
    $this->assertResponse(200);
    $this->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    $this->assertText('Page 2');
  }

  /**
   * Helper to browse aliased fixture pages.
   */
  public function browseFixtureMinisiteAliased($alias, $description, $assets_paths) {
    $this->drupalGet($alias);
    $this->assertResponse(200);

    // Assert that a link to a minisite is present.
    $this->assertLink($description);
    $this->assertLinkByHref($alias . '/' . $assets_paths[0]);

    // Start browsing the minisite.
    $this->clickLink($description);

    // Assert first index path as aliased.
    $this->assertUrl($alias . '/' . $assets_paths[0]);
    $this->assertResponse(200);
    $this->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    // Brose minisite pages starting from index page.
    $this->assertText('Index page');
    $this->assertLink('Go to Page 1');
    $this->clickLink('Go to Page 1');
    $this->assertResponse(200);
    $this->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    $this->assertText('Page 1');
    $this->assertUrl($alias . '/' . $assets_paths[1]);

    $this->assertLink('Go to Page 2');
    $this->clickLink('Go to Page 2');
    $this->assertResponse(200);
    $this->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    $this->assertText('Page 2');
    $this->assertUrl($alias . '/' . $assets_paths[2]);

    // Navigate to the page using URL with a query.
    $this->drupalGet($alias . '/' . $assets_paths[1], [
      'query' => [
        'param' => 'val',
      ],
      'fragment' => 'someid',
    ]);
    $this->assertResponse(200);
    $this->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    // Get non-document file through an alias.
    $this->drupalGet($alias . '/' . $assets_paths[3]);
    $this->assertResponse(200);
    $this->assertHeader('Content-Type', 'image/jpeg');
    $this->assertHeader('Content-Length', (string) filesize($this->getFixtureFileDir() . DIRECTORY_SEPARATOR . 'example.jpeg'));
  }

  /**
   * Create a stub asset path.
   *
   * @return string
   *   Path for a stub asset.
   */
  protected function getStubAssetPath() {
    $randomizer = new Random();

    $prefix = 'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/';
    $suffix = '.html';

    $dir_path = $randomizer->name(10) . '/';
    // The full path of the file with the scheme should be exactly 2048
    // characters long.
    // Note that most of the browsers support URLs length under 2048 characters.
    $file_path = $randomizer->name(2048 - strlen($dir_path) - strlen($prefix) - strlen($suffix)) . $suffix;
    $path = $prefix . $dir_path . $file_path;

    return $path;
  }

}
