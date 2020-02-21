<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;

/**
 * Tests the minisite file upload and browsing with alias set in pathauto.
 *
 * These are behavioural-driven tests. If these tests are failing - the module
 * does not work correctly and the users will experience issues.
 *
 * @group minisite
 */
class UploadBrowseAliasPathautoTest extends MinisiteTestBase {

  use PathautoTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'pathauto', 'token'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests ZIP file upload and browsing minisite pages with Pathauto alias.
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

    // Create pathauto pattern.
    $this->createPattern('node', mb_strtolower($this->randomMachineName()) . '/' . '[node:title]');

    // Create a field and a node with Pathauto enabled.
    $edit = [
      'path[0][pathauto]' => TRUE,
    ];
    $field_name = $this->createFieldAndNode($this->contentType, $node_title, $minisite_description, $edit);
    $node = $this->drupalGetNodeByTitle($node_title);
    $nid = $node->id();

    // Assert that an alias was created for a node.
    $this->assertEntityAliasExists($node);

    // Assert that minisite archive file was uploaded.
    $this->assertMinisiteUploaded($node, $field_name, $test_archive_assets);

    // Browse fixture minisite using Pathauto-generated alias.
    $node_alias = $node->path->get(0)->getValue()['alias'];
    $this->browseFixtureMinisiteAliased($node_alias, $minisite_description, $test_archive_assets);

    // Disable pathauto alias generation, manually update node's alias and
    // assert that update has been applied to the paths of the minisite.
    $node_alias_updated = '/a' . $this->randomMachineName();
    $edit = [
      'path[0][pathauto]' => FALSE,
      'path[0][alias]' => $node_alias_updated,
    ];
    $this->drupalPostForm("node/$nid/edit", $edit, $this->t('Save'));

    // Browse fixture minisite using updated manual alias.
    $this->browseFixtureMinisiteAliased($node_alias_updated, $minisite_description, $test_archive_assets);

    // Enable pathauto and assert that re-generated path alias has been
    // applied.
    $edit = [
      'path[0][pathauto]' => TRUE,
    ];
    $this->drupalPostForm("node/$nid/edit", $edit, $this->t('Save'));
    $node = Node::load($nid);
    $this->assertEntityAliasExists($node);

    // Browse fixture minisite using updated Pathauto-generated alias.
    $this->browseFixtureMinisiteAliased($node_alias, $minisite_description, $test_archive_assets);

    // Delete node.
    $this->drupalPostForm("node/$nid/delete", [], $this->t('Delete'));
    $this->assertResponse(200);

    // Assert that Minisite assets were removed.
    $this->assertMinisiteRemoved($node, $field_name, $test_archive_assets);
  }

}
