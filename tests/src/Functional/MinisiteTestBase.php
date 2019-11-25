<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\minisite\Minisite;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\minisite\Traits\FixtureTrait;

/**
 * Provides methods specifically for testing Minisite module's field handling.
 */
abstract class MinisiteTestBase extends BrowserTestBase {

  use FixtureTrait;
  use FieldCreationTrait;
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fixtureSetUp();

    $this->adminUser = $this->drupalCreateUser([
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
    ]);
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

    $node_storage = $this->container->get('entity.manager')->getStorage('node');
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
   * Overrides PHPUnit\Framework\Assert::assertFileExists() to also work with
   * file entities.
   *
   * @param \Drupal\File\FileInterface|string $file
   *   Either the file entity or the file URI.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  public static function assertFileExists($file, $message = NULL) {
    $message = isset($message) ? $message : new FormattableMarkup('File %file exists on the disk.', ['%file' => $file->getFileUri()]);
    $filename = $file instanceof FileInterface ? $file->getFileUri() : $file;
    parent::assertFileExists($filename, $message);
  }

  /**
   * Asserts that a file exists in the database.
   */
  public function assertFileEntryExists($file, $message = NULL) {
    $this->container->get('entity.manager')->getStorage('file')->resetCache();
    $db_file = File::load($file->id());
    $message = isset($message) ? $message : new FormattableMarkup('File %file exists in database at the correct path.', ['%file' => $file->getFileUri()]);
    $this->assertEqual($db_file->getFileUri(), $file->getFileUri(), $message);
  }

  /**
   * Asserts that a file does not exist in the database.
   */
  public function assertFileEntryNotExists($file, $message = NULL) {
    $this->container->get('entity.manager')->getStorage('file')->resetCache();
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
      'parent/index.html' => $this->fixtureHtmlPage('Index page', $this->fixtureLink('Go to Page 1', 'page1.html')),
      'parent/page1.html' => $this->fixtureHtmlPage('Page 1', $this->fixtureLink('Go to Page 2', 'page2.html')),
      'parent/page2.html' => $this->fixtureHtmlPage('Page 2'),
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
    $this->assertFileExists(Minisite::getCommonArchiveDir() . DIRECTORY_SEPARATOR . $file->getFilename(), 'Archive file exists');
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
    $actual_files = array_keys(file_scan_directory(Minisite::getCommonAssetDir(), '/.*/'));

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
    $actual_files = array_keys(file_scan_directory(Minisite::getCommonAssetDir(), '/.*/'));
    foreach ($files as $test_file) {
      $found_files = array_filter($actual_files, function ($value) use ($test_file) {
        return substr($value, -strlen($test_file)) === $test_file;
      });

      $this->assertTrue(empty($found_files), 'Asset file does not exist');
    }
  }

}
