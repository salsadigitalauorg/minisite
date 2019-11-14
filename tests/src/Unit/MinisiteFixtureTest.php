<?php

namespace Drupal\Tests\minisite\Unit;

use Drupal\testmode\Testmode;
use Drupal\Tests\minisite\Traits\FixtureTrait;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class MinisiteFixtureTest.
 *
 * Tests for fixture creation trait (tests for test helpers).
 *
 * @group Minisite
 *
 * @package Drupal\testmode\Tests
 */
class MinisiteFixtureTest extends UnitTestCase {

  use FixtureTrait;

  protected function setUp() {
    parent::setUp();
    $this->fixtureSetUp();
  }

  protected function tearDown() {
    parent::tearDown();
    $this->fixtureTearDown();
  }

  /**
   * Test SetUp and TearDown methods for the trait.
   */
  public function testSetupTeardown() {
    // $fixtureDir is already populated from the setUp() of this test,
    // so we need to remove it.
    $fs = new Filesystem();
    $fs->remove($this->fixtureDir);

    $this->fixtureSetUp();
    $first_dir = $this->fixtureDir;
    $this->assertNotEmpty($first_dir, 'fixtureSetUp() populates $fixtureDir variable');
    $this->assertDirectoryExists($first_dir, 'fixtureSetUp() creates directory');

    $this->fixtureSetUp();
    $second_dir = $this->fixtureDir;
    $this->assertNotEmpty($second_dir, 'fixtureSetUp() populates $fixtureDir variable');
    $this->assertDirectoryExists($second_dir, 'fixtureSetUp() creates directory');
    $this->assertNotEquals($first_dir, $second_dir, 'fixtureSetUp() creates new directory on every call');

    $this->fixtureTearDown();
    $this->assertDirectoryNotExists($second_dir, 'fixtureTearDown() removes directory');
    $this->assertEmpty($this->fixtureDir, 'fixtureTearDown() removes the value from $fixtureDir');
  }

  /**
   * @dataProvider providerFiles
   */
  public function t1estFiles() {

  }

  public function providerFiles() {

  }

}
