<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\minisite\Traits\FixtureTrait;

/**
 * Provides methods specifically for testing Minisite module's field handling.
 */
abstract class MinisiteTestBase extends BrowserTestBase {

  use FixtureTrait;

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

  /**
   * Content type used to create the field on.
   *
   * @var string
   */
  protected $contentType = 'article';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fixtureSetUp();

    $this->adminUser = $this->drupalCreateUser(['access content', 'access administration pages', 'administer site configuration', 'administer users', 'administer permissions', 'administer content types', 'administer node fields', 'administer node display', 'administer nodes', 'bypass node access']);
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(['type' => $this->contentType, 'name' => 'Article']);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();

    $this->fixtureTearDown();
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

}
