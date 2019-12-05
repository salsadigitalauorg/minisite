<?php

namespace Drupal\Tests\minisite\Functional;

use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Core\File\Exception\NotRegularFileException;
use Drupal\Core\Language\Language;
use Drupal\minisite\Asset;
use Drupal\minisite\Exception\AssetException;

/**
 * Class AssetTest.
 *
 * Tests for Asset class.
 *
 * @group minisite
 */
class AssetTest extends MinisiteTestBase {

  /**
   * Test working with Asset class instance.
   */
  public function testAssetInstance() {
    $asset = new Asset(
      'node',
      $this->contentType,
      1,
      2,
      Language::LANGCODE_DEFAULT,
      'field_minisite_test',
      'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/page1.html'
    );

    // Assert getters without alias set.
    $this->assertEqual('public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/page1.html', $asset->getUri());
    $this->assertContains('minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/page1.html', $asset->getUrl());

    // Assert getters with alias set.
    $asset->setAliasPrefix('some/alias');
    $this->assertEqual('public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/page1.html', $asset->getUri());
    $this->assertContains('/some/alias/rootpath/subpath/page1.html', $asset->getUrl());
    $this->assertNotContains('minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5', $asset->getUrl());

    // Assert other getters.
    $this->assertEquals(Language::LANGCODE_DEFAULT, $asset->getLanguage());
    $this->assertTrue($asset->isDocument());
    $this->assertFalse($asset->isIndex());

    // Assert saving.
    $this->assertNull($asset->id());

    $asset->save();
    $this->assertNotNull($asset->id());
    $previous_id = $asset->id();

    $asset->save();
    $this->assertEquals($previous_id, $asset->id(), 'Id has not changed after re-save');

    // Assert loading.
    $asset2 = Asset::load($previous_id);
    $this->assertNotNull($asset2);
    $this->assertEquals($previous_id, $asset2->id());

    $asset3 = Asset::loadByAlias('/some/alias/rootpath/subpath/page1.html');
    $this->assertNotNull($asset3);
    $this->assertEquals($previous_id, $asset3->id());

    $asset4 = Asset::loadByUri('public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/page1.html');
    $this->assertNotNull($asset4);
    $this->assertEquals($previous_id, $asset4->id());

    // Deleting.
    try {
      $asset4->delete();
    }
    catch (NotRegularFileException | NotRegularDirectoryException $exception) {
      // This test is not dealing with real files, so allow exceptions for
      // file removals.
    }
    $this->assertNull($asset4->id());
    $asset5 = Asset::load($previous_id);
    $this->assertNull($asset5);
  }

  /**
   * Test Asset::fromValues().
   *
   * @dataProvider dataProviderAssetFromValues
   * @covers \Drupal\minisite\Asset::fromValues
   */
  public function testAssetFromValues($values, $expect_exception) {
    if ($expect_exception) {
      $this->expectException(AssetException::class);
    }
    $instance = Asset::fromValues($values);
    $this->assertNotNull($instance);
  }

  /**
   * Data provider for testAssetFromValues.
   */
  public function dataProviderAssetFromValues() {
    return [
      // All normally provided keys.
      [
        [
          'entity_type' => 'someval',
          'entity_bundle' => 'someval',
          'entity_id' => 'someval',
          'entity_rid' => 'someval',
          'entity_language' => 'someval',
          'field_name' => 'someval',
          'source' => 'someval',
        ],
        FALSE,
      ],

      // Only required.
      [
        [
          'entity_type' => 'someval',
          'entity_bundle' => 'someval',
          'entity_id' => 'someval',
          'entity_language' => 'someval',
          'field_name' => 'someval',
          'source' => 'someval',
        ],
        FALSE,
      ],

      // Missing keys.
      [
        [
          'entity_type' => 'someval',
          'entity_bundle' => 'someval',
          'entity_id' => 'someval',
          'entity_language' => 'someval',
          'field_name' => 'someval',
        ],
        TRUE,
      ],

      // Fields with no values.
      [
        [
          'entity_type' => '',
          'entity_bundle' => 'someval',
          'entity_id' => NULL,
          'entity_language' => 'someval',
          'field_name' => FALSE,
          'source' => 'someval',
        ],
        TRUE,
      ],
    ];
  }

  /**
   * Test Asset::isIndex().
   *
   * @dataProvider dataProviderIsIndex
   * @covers \Drupal\minisite\Asset::isIndex
   */
  public function testIsIndex($path, $is_index) {
    $instance = new Asset(
      'node',
      $this->contentType,
      1,
      2,
      Language::LANGCODE_DEFAULT,
      'field_minisite_test',
      $path
    );

    $this->assertEqual($instance->isIndex(), $is_index);
  }

  /**
   * Data provider for testIsIndex.
   */
  public function dataProviderIsIndex() {
    return [
      ['public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/index.html', TRUE],
      ['public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/page.html', FALSE],
      ['public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/index.html', FALSE],
      ['public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/page.html', FALSE],
    ];
  }

}
