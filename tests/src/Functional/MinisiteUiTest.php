<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the minisite field creation through UI.
 *
 * @group file
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
    $archive_file_absolute = $this->fixtureCreateArchive([
      'parent/index.html' => $this->fixtureHtmlPage('Index page', $this->fixtureLink('Go to Page 1', 'page1.html')),
      'parent/page1.html' => $this->fixtureHtmlPage('Page 1', $this->fixtureLink('Go to Page 2', 'page2.html')),
      'parent/page2.html' => $this->fixtureHtmlPage('Page 2'),
    ]);
    $archive_file = basename($archive_file_absolute);

    // Manually clear cache on the tester side.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Create node and upload fixture file.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[field_' . $field_name . '_' . 0 . ']' => $archive_file_absolute,
    ];
    $this->drupalPostForm("node/add/$type_name", $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Visit note and start browsing minisite.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertLink($archive_file);
    $this->clickLink($archive_file);

    // Brose minisite pages starting from index page.
    $this->assertText('Index page');
    $this->assertLink('Go to Page 1');
    $this->clickLink('Go to Page 1');

    $this->assertText('Page 1');
    $this->assertLink('Go to Page 2');
    $this->clickLink('Go to Page 2');

    $this->assertText('Page 2');
  }

}
