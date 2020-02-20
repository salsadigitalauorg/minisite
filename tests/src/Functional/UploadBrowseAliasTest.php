<?php

namespace Drupal\Tests\minisite\Functional;

/**
 * Tests the minisite file upload and browsing through UI with alias.
 *
 * These are behavioural-driven tests. If these tests are failing - the module
 * does not work correctly and the users will experience issues.
 *
 * @group minisite
 */
class UploadBrowseAliasTest extends MinisiteTestBase {

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
   * Tests ZIP file upload and browsing minisite pages with alias.
   *
   * This is a simple UI test using archive fixture in default format.
   * If this test does not pass - the module definitely does not work as
   * required.
   */
  public function testUploadAndBrowsingAlias() {
    // Create test values.
    $test_archive_assets = array_keys($this->getTestFilesStubValid());
    $node_title = $this->randomMachineName();
    $minisite_description = 'D' . $this->randomMachineName();
    $node_alias = '/a' . $this->randomMachineName();

    // Create a field and a node with custom path alias.
    $edit = [
      'path[0][alias]' => $node_alias,
    ];

    $field_name = $this->createFieldAndNode($this->contentType, $node_title, $minisite_description, $edit);
    $node = $this->drupalGetNodeByTitle($node_title);
    $nid = $node->id();

    // Assert that minisite archive file was uploaded.
    $this->assertMinisiteUploaded($node, $field_name, $test_archive_assets);

    // Browse fixture minisite using manually provided alias.
    $node_alias = $node->path->get(0)->getValue()['alias'];
    $this->browseFixtureMinisiteAliased($node_alias, $minisite_description, $test_archive_assets);

    // Updated node's alias and assert that update has been applied.
    $node_alias_updated = '/a' . $this->randomMachineName();
    $edit = [
      'path[0][alias]' => $node_alias_updated,
    ];
    $this->drupalPostForm("node/$nid/edit", $edit, $this->t('Save'));

    // Browse fixture minisite using updated manually provided alias.
    $this->browseFixtureMinisiteAliased($node_alias_updated, $minisite_description, $test_archive_assets);

    // Remove node's alias and assert that update has been applied.
    $edit = [
      'path[0][alias]' => '',
    ];
    $this->drupalPostForm("node/$nid/edit", $edit, $this->t('Save'));
    $this->browseFixtureMinisite($node, $minisite_description);

    // Delete node.
    $this->drupalPostForm("node/$nid/delete", [], $this->t('Delete'));
    $this->assertResponse(200);

    // Assert that Minisite assets were removed.
    $this->assertMinisiteRemoved($node, $field_name, $test_archive_assets);
  }

}
