<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Component\Utility\Html;
use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Provides methods specifically for testing Minisite module's field handling.
 */
abstract class MinisiteTestBase extends BrowserTestBase {

  use MinisiteCreationTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'file', 'field', 'field_ui', 'minisite'];

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected $contentType = 'article';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['access content', 'access administration pages', 'administer site configuration', 'administer users', 'administer permissions', 'administer content types', 'administer node fields', 'administer node display', 'administer nodes', 'bypass node access']);
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(['type' => $this->contentType, 'name' => 'Article']);
  }

  /**
   * Retrieves a sample file of the specified type.
   *
   * @return \Drupal\file\FileInterface
   *   The new unsaved file entity.
   */
  public function getTestFile($type_name, $size = NULL) {
    // Get a file to upload.
    $file = current($this->drupalGetTestFiles($type_name, $size));

    // Add a filesize property to files as would be read by
    // \Drupal\file\Entity\File::load().
    $file->filesize = filesize($file->uri);

    return File::create((array) $file);
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


  protected function createFixtureArchive($filename, $structure) {

  }

}
