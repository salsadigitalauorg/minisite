<?php

namespace Drupal\Tests\minisite\Functional;

/**
 * Tests the minisite file upload and browsing through UI.
 *
 * These are behavioural-driven tests. If these tests are failing - the module
 * does not work correctly and the users will experience issues.
 *
 * @group minisite
 */
class UploadBrowseTest extends MinisiteTestBase {

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
   * Tests ZIP file upload and browsing minisite pages.
   *
   * This is a simple UI test using archive fixture in default format.
   * If this test does not pass - the module definitely does not work as
   * required.
   */
  public function testUploadAndBrowsing() {
    // Create test values.
    $test_archive_assets = array_keys($this->getTestFilesStubValid());
    $node_title = $this->randomMachineName();

    // Create a field and a node.
    $field_name = $this->createFieldAndNode($this->contentType, $node_title);
    $node = $this->drupalGetNodeByTitle($node_title);
    $nid = $node->id();

    // Assert that minisite archive file was uploaded.
    $this->assertMinisiteUploaded($node, $field_name, $test_archive_assets);

    $test_archive = $this->getUploadedArchiveFile($node, $field_name);
    $this->browseFixtureMinisite($node, $test_archive->getFilename());

    // Delete node.
    $this->drupalPostForm("node/$nid/delete", [], $this->t('Delete'));
    $this->assertResponse(200);

    // Assert that Minisite assets were removed.
    $this->assertMinisiteRemoved($node, $field_name, $test_archive_assets);
  }

  /**
   * Tests ZIP file upload and removal, without removing a node.
   *
   * This is a simple UI test using archive fixture in default format.
   * If this test does not pass - the module definitely does not work as
   * required.
   */
  public function testUploadAndRemoval() {
    // Create test values.
    $test_archive_assets = array_keys($this->getTestFilesStubValid());
    $node_title = $this->randomMachineName();

    // Create a field and a node.
    $field_name = $this->createFieldAndNode($this->contentType, $node_title);
    $node = $this->drupalGetNodeByTitle($node_title);
    $nid = $node->id();

    // Assert that minisite archive file was uploaded.
    $this->assertMinisiteUploaded($node, $field_name, $test_archive_assets);

    $test_archive = $this->getUploadedArchiveFile($node, $field_name);
    $this->browseFixtureMinisite($node, $test_archive->getFilename());

    // Remove the uploaded file and save the node.
    $this->drupalPostForm("node/$nid/edit", [], $this->t('Remove'));
    $this->drupalPostForm(NULL, [], $this->t('Save'));
    $this->assertResponse(200);

    // Assert that Minisite assets were removed.
    $this->assertMinisiteRemoved($node, $field_name, $test_archive_assets);
  }

}
