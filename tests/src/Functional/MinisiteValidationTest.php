<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the minisite field validation.
 *
 * @group minisite
 */
class MinisiteValidationTest extends MinisiteTestBase {

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
   * Tests the required property on minisite field.
   */
  public function testValidateRequired() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $field_name = strtolower($this->randomMachineName());
    $storage = $this->createMinisiteField($field_name, 'node', $this->contentType, [], ['required' => '1']);
    $field = FieldConfig::loadByName('node', $this->contentType, $field_name);

    // Try to post a new node without uploading a file.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm('node/add/' . $this->contentType, $edit, $this->t('Save'));
    $this->assertRaw($this->t('@title field is required.', ['@title' => $field->getLabel()]));

    // Create a new node with the uploaded file.
    $test_file = $this->getTestArchiveValid();
    $nid = $this->uploadNodeFile($test_file, $field_name, $this->contentType);
    $this->assertTrue($nid !== FALSE, new FormattableMarkup('uploadNodeFile(@test_file, @field_name, @type_name) succeeded', [
      '@test_file' => $test_file->getFileUri(),
      '@field_name' => $field_name,
      '@type_name' => $this->contentType,
    ]));

    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);

    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file, 'File exists after uploading to the required field.');
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading to the required field.');

    // Try again with a multiple value field.
    $storage->delete();
    $this->createMinisiteField($field_name, 'node', $this->contentType, ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED], ['required' => '1']);

    // Try to post a new node without uploading a file in the multivalue field.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm('node/add/' . $this->contentType, $edit, $this->t('Save'));
    $this->assertRaw($this->t('@title field is required.', ['@title' => $field->getLabel()]));

    // Create a new node with the uploaded file into the multivalue field.
    $nid = $this->uploadNodeFile($test_file, $field_name, $this->contentType);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file, 'File exists after uploading to the required multiple value field.');
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading to the required multiple value field.');
  }

  /**
   * Tests the archive format on minisite field.
   */
  public function testValidateArchiveFormat() {
    $field_name = strtolower($this->randomMachineName());
    $this->createMinisiteField($field_name, 'node', $this->contentType, [], ['required' => '1']);

    // Try uploading a file with correct extension, but invalid format.
    $test_file = $this->getTestArchiveInvalidFormat();
    $this->uploadNodeFile($test_file, $field_name, $this->contentType);
    $this->assertRaw($this->t('The specified file %filename could not be uploaded.', ['%filename' => $test_file->getFilename()]));
    $this->assertRaw($this->t('File @filename is not an archive file.', ['@filename' => $test_file->getFilename()]));
  }

}