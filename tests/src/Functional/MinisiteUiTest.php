<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the minisite field creation through UI.
 *
 * @group minisite
 */
class MinisiteUiTest extends MinisiteTestBase {

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

    // Manually clear cache on the tester side.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create node and upload fixture file.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[field_' . $field_name . '_' . 0 . ']' => $test_archive->getFileUri(),
    ];
    $this->drupalPostForm("node/add/$type_name", $edit, $this->t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Assert that files exist.
    $node_file = File::load($node->{'field_' . $field_name}->target_id);
    $this->assertArchiveFileExist($node_file);
    $this->assertAssetFilesExist(array_keys($this->getTestFilesStubValid()));

    // Visit note and start browsing minisite.
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

    $this->drupalPostForm('node/' . $node->id() . '/delete', [], $this->t('Delete'));
    $this->assertResponse(200);
    $this->assertArchiveFileNotExist($node_file);
    $this->assertAssetFilesNotExist(array_keys($this->getTestFilesStubValid()));
  }

  /**
   * Tests that only cardinality 1 is allowed.
   */
  public function testCardinality() {
    $type_name = $this->contentType;

    $field_name = 'ms_fn_' . strtolower($this->randomMachineName(4));
    $field_label = 'ms_fl_' . strtolower($this->randomMachineName(4));

    $initial_edit = [
      'new_storage_type' => 'minisite',
      'label' => $field_label,
      'field_name' => $field_name,
    ];
    $this->drupalPostForm("admin/structure/types/manage/$type_name/fields/add-field", $initial_edit, $this->t('Save and continue'));
    $this->assertRaw($this->t('These settings apply to the %label field everywhere it is used.', ['%label' => $field_label]), 'Storage settings page was displayed.');

    $this->assertRaw($this->t('This field cardinality is set to 1 and cannot be configured.'), 'Cardinality is restricted to 1.');
  }

}
