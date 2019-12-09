<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\file\Entity\File;
use Drupal\minisite\Asset;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the minisite file upload and browsing through UI.
 *
 * These are behavioural-driven tests. If these tests are failing - the module
 * does not work correctly and the users will experience issues.
 *
 * @group minisite
 */
class UploadBrowseTest extends MinisiteTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests file upload and browsing minisite pages.
   *
   * This is a simple UI test using archive fixture in default format.
   * If this test does not pass - the module definitely does not work as
   * required.
   */
  public function testUploadAndBrowsing() {
    $type_name = $this->contentType;

    $field_name = 'ms_fn_' . strtolower($this->randomMachineName(4));
    $field_label = 'ms_fl_' . strtolower($this->randomMachineName(4));

    // Create field through UI.
    // Note that config schema is also validated when field is created.
    $storage_edit = ['settings[uri_scheme]' => 'public'];
    $this->fieldUIAddNewField("admin/structure/types/manage/$type_name", $field_name, $field_label, 'minisite', $storage_edit);

    // Create valid fixture archive.
    // All files must reside in the top-level directory and archive must contain
    // index.html file.
    $test_archive = $this->getTestArchiveValid();
    $test_archive_assets = array_keys($this->getTestFilesStubValid());

    // Manually clear cache on the tester side.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create node and upload fixture file.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[field_' . $field_name . '_' . 0 . ']' => $test_archive->getFileUri(),
    ];
    $this->drupalPostForm("node/add/$type_name", $edit, $this->t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Assert that file exist.
    $archive_file = File::load($node->{'field_' . $field_name}->target_id);
    $this->assertArchiveFileExist($archive_file);
    $this->assertAssetFilesExist($test_archive_assets);

    // Visit node and start browsing minisite.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertLink($test_archive->getFilename());
    $this->clickLink($test_archive->getFilename());

    // Brose minisite pages starting from index page.
    $this->assertText('Index page');
    $this->assertLink('Go to Page 1');
    $this->clickLink('Go to Page 1');

    $this->assertText('Page 1');
    $this->assertLink('Go to Page 2');
    $this->clickLink('Go to Page 2');

    $this->assertText('Page 2');

    // Delete node.
    $this->drupalPostForm('node/' . $node->id() . '/delete', [], $this->t('Delete'));
    $this->assertResponse(200);

    // Assert that files no longer exist.
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

}
