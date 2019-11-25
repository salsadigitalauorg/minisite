<?php

namespace Drupal\Tests\minisite\Unit;

use Drupal\Core\Archiver\Zip;
use Drupal\Tests\minisite\Traits\FixtureTrait;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FixtureTest.
 *
 * Tests for fixture creation trait (tests for test helpers).
 *
 * @group minisite
 *
 * @package Drupal\testmode\Tests
 */
class FixtureTest extends UnitTestCase {

  use FixtureTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->fixtureSetUp();
  }

  /**
   * {@inheritdoc}
   */
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
   * Test fixtureCreateFiles() method.
   */
  public function testFixtureCreateFiles() {
    // @codingStandardsIgnoreStart
    $files = [
      'dir1',
      'file1' => 'content1',
      'dir2/file21' => 'content21',
      'dir2/file22' => 'content22',
      'dir3/dir31/dir/311',
    ];
    // @codingStandardsIgnoreEnd

    $expected_files = [
      $this->fixtureDir . \DIRECTORY_SEPARATOR . 'dir1' => 'dir1',
      $this->fixtureDir . \DIRECTORY_SEPARATOR . 'file1' => 'file1',
      $this->fixtureDir . \DIRECTORY_SEPARATOR . 'dir2/file21' => 'dir2/file21',
      $this->fixtureDir . \DIRECTORY_SEPARATOR . 'dir2/file22' => 'dir2/file22',
      $this->fixtureDir . \DIRECTORY_SEPARATOR . 'dir3/dir31/dir/311' => 'dir3/dir31/dir/311',
    ];

    $actual_files = $this->fixtureCreateFiles($files);
    $this->assertEquals($expected_files, $actual_files);

    $this->assertDirectoryExists($this->fixtureDir . \DIRECTORY_SEPARATOR . 'dir1');
    $this->assertFileExists($this->fixtureDir . \DIRECTORY_SEPARATOR . 'file1');
    $this->assertStringEqualsFile($this->fixtureDir . \DIRECTORY_SEPARATOR . 'file1', 'content1');
    $this->assertStringEqualsFile($this->fixtureDir . \DIRECTORY_SEPARATOR . 'dir2/file21', 'content21');
    $this->assertStringEqualsFile($this->fixtureDir . \DIRECTORY_SEPARATOR . 'dir2/file22', 'content22');
    $this->assertDirectoryExists($this->fixtureDir . \DIRECTORY_SEPARATOR . 'dir3/dir31/dir/311');
  }

  /**
   * Test fixtureCreateArchive() method.
   */
  public function testFixtureCreateArchive() {
    // @codingStandardsIgnoreStart
    $files = [
      'dir1',
      'file1' => 'content1',
      'dir2/file21' => 'content21',
      'dir2/file22' => 'content22',
      'dir3/dir31/dir/311',
    ];
    // @codingStandardsIgnoreEnd

    $expected_files = [
      'dir1/',
      'file1',
      'dir2/file21',
      'dir2/file22',
      'dir3/dir31/dir/311/',
    ];

    $archive_filename = $this->fixtureCreateArchive($files, 'zip');
    $this->assertFileExists($archive_filename);

    $archive = new Zip($archive_filename);
    $actual_files = $archive->listContents();

    sort($expected_files);
    sort($actual_files);

    $this->assertEquals($expected_files, $actual_files);
  }

}
