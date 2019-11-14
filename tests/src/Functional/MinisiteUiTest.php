<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the minisite field creation through UI.
 *
 * @group file
 */
class MinisiteUiTest extends MinisiteTestBase {

  use CommentTestTrait;
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
   * Tests upload and remove buttons for a single-valued Minisite field.
   */
  public function testSingleValuedWidget() {
    $type_name = $this->contentType;

    $field_name = 'ms_' . strtolower($this->randomMachineName());
    $field_label = $this->randomMachineName();

    // Create field through UI.
    $storage_edit = [
      'settings[uri_scheme]' => 'public',
    ];
    $this->fieldUIAddNewField("admin/structure/types/manage/$type_name", $field_name, $field_label, 'minisite', $storage_edit);

    // @todo:
    // + Create content type
    // + Add field
    // + Adjust field config - using defaults
    // Create fixture archive
    // Create node and upload fixture archive
    // Assert node visibility
    // Navigate to the path of the site and assert pages
  }

}
