<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Component\Utility\Random;
use Drupal\minisite\Exception\MissingArchiveException;
use Drupal\minisite\Minisite;
use Drupal\node\Entity\Node;

/**
 * Class MinisiteTest.
 *
 * Tests for Minisite class.
 *
 * @group minisite
 */
class MinisiteTest extends MinisiteTestBase {

  /**
   * Test working with Minisite class instance.
   *
   * @covers \Drupal\minisite\Minisite::__construct
   * @covers \Drupal\minisite\Minisite::getArchiveFile
   * @covers \Drupal\minisite\Minisite::setArchiveFile
   * @covers \Drupal\minisite\Minisite::getDescription
   * @covers \Drupal\minisite\Minisite::setDescription
   * @covers \Drupal\minisite\Minisite::getAssetDirectory
   * @covers \Drupal\minisite\Minisite::getIndexAssetUri
   * @covers \Drupal\minisite\Minisite::getIndexAssetUrl
   */
  public function testMinisiteInstance() {
    $randomizer = new Random();

    /** @var \Drupal\file\Entity\File $archive_file1 */
    $archive_file1 = $this->getTestArchiveValid();
    /** @var \Drupal\file\Entity\File $archive_file2 */
    $archive_file2 = $this->getTestArchiveValid();
    // List of files within the archive (the same for both files above).
    $test_archive_assets = array_keys($this->getTestFilesStubValid());

    // Create a field and a node.
    $field_name = 'field_' . $randomizer->string();
    $node = Node::create([
      'title' => $randomizer->string(),
      'type' => $randomizer->string(),
    ]);

    $minisite = new Minisite($node, $field_name, $archive_file1);
    $this->assertNotNull($minisite, 'Can create instance of the class using constructor');

    $this->assertEquals('', $minisite->getDescription(), 'Initial description value is empty');

    $description = $randomizer->string();
    $minisite->setDescription($description);
    $this->assertEquals($description, $minisite->getDescription(), 'setDescription() sets and getDescription() retrieves correct description value');

    $this->assertEquals($archive_file1, $minisite->getArchiveFile(), 'getArchiveFile() returns archive managed file object');
    $asset_dir1 = $minisite->getAssetDirectory();

    $minisite->setArchiveFile($archive_file2);
    $this->assertEquals($archive_file2, $minisite->getArchiveFile(), 'setArchiveFile() set new archive managed file object');
    $asset_dir2 = $minisite->getAssetDirectory();
    $this->assertNotEquals($asset_dir1, $asset_dir2, 'Asset directory was updated when archive managed file changed');

    $index_uri = $asset_dir2 . DIRECTORY_SEPARATOR . $test_archive_assets[0];
    $this->assertEquals($index_uri, $minisite->getIndexAssetUri(), 'getIndexAssetUri() returns correct URI of the index asset');
    $this->assertTrue(is_readable($index_uri), 'Asset file exists');

    $index_url = file_url_transform_relative(file_create_url($asset_dir2 . DIRECTORY_SEPARATOR . $test_archive_assets[0]));
    $this->assertEquals($index_url, $minisite->getIndexAssetUrl(), 'getIndexAssetUrl() returns correct URI of the index asset');

    // Idempotence tests.
    //
    // Wait for 1 second to guarantee new timestamp on the newly created files,
    // if any.
    sleep(1);

    $index_asset_creation_time = filemtime($index_uri);
    $minisite2 = new Minisite($node, $field_name, $archive_file2);
    $this->assertEquals($asset_dir2, $minisite2->getAssetDirectory(), 'Idempotence: Asset directory was not changed after another instance creation');
    $this->assertTrue(is_readable($index_uri), 'Idempotence: Asset file exists after another instance creation');
    $this->assertEquals(filemtime($index_uri), $index_asset_creation_time, 'Idempotence: Asset file was not re-created after another instance creation');
  }

  /**
   * Test that missing physical file throws exception.
   *
   * @covers \Drupal\minisite\Minisite::setArchiveFile
   * @covers \Drupal\minisite\Minisite::validateArchive
   */
  public function testMissingFile() {
    $randomizer = new Random();

    /** @var \Drupal\file\Entity\File $archive_file */
    $archive_file = $this->getTestArchiveValid();

    // Create a field and a node.
    $field_name = 'field_' . $randomizer->string();
    $node = Node::create([
      'title' => $randomizer->string(),
      'type' => $randomizer->string(),
    ]);

    $uri = $archive_file->getFileUri();
    unlink($uri);
    $this->expectException(MissingArchiveException::class);
    $this->expectExceptionMessage(sprintf('Archive file "%s" is missing.', $uri));

    $minisite = new Minisite($node, $field_name, $archive_file);
    $this->assertNull($minisite);
  }

}
