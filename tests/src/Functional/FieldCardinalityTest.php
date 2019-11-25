<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the minisite field cardinality.
 *
 * @group minisite
 */
class FieldCardinalityTest extends MinisiteTestBase {

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
