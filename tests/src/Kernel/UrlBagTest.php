<?php

namespace Drupal\Tests\minisite\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\minisite\Exception\UrlBagException;
use Drupal\minisite\UrlBag;
use Drupal\Tests\minisite\Traits\MockHelperTrait;

/**
 * Class UrlBagTest.
 *
 * Tests URL bag.
 *
 * @group minisite
 *
 * @package Drupal\testmode\Tests
 */
class UrlBagTest extends KernelTestBase {

  use MockHelperTrait;

  /**
   * Tests toLocal() method.
   *
   * @dataProvider dataProviderToLocal
   * @covers \Drupal\minisite\UrlBag::toLocal
   */
  public function testToLocal($base_url, $url, $expected, $expect_exception = FALSE) {
    if ($expect_exception) {
      $this->expectException(UrlBagException::class);
    }
    $actual = $this->callProtectedMethod(UrlBag::class, 'toLocal', [$url, $base_url]);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testToLocal.
   */
  public function dataProviderToLocal() {
    return [
      [
        'http://example.com',
        'http://example.com/page1',
        '/page1',
      ],
      [
        'http://example.com',
        'http://example.com/sub/path/page1',
        '/sub/path/page1',
      ],

      [
        'http://example.com',
        '/page1',
        '/page1',
      ],

      [
        'http://example.com',
        '/sub/path/page1',
        '/sub/path/page1',
      ],

      [
        'http://example.com',
        'page1',
        '/page1',
      ],
      [
        'http://example.com',
        'subpath/page1',
        '/subpath/page1',
      ],

      [
        'http://example.com',
        'http://otherexample.com/page1',
        '',
        TRUE,
      ],

      [
        'http://example.com',
        'http://otherexample.com',
        '',
        TRUE,
      ],

      [
        'http://example.com',
        'http://example.com',
        '',
        TRUE,
      ],

      // File.
      [
        'http://example.com',
        'public://page1',
        'public://page1',
      ],
      [
        'http://example.com/hostpath',
        'public://page1',
        'public://page1',
      ],

      // Base URL is a subdir.
      [
        'http://example.com/subdir',
        '/page1',
        '/page1',
      ],
      [
        'http://example.com/subdir',
        'page1',
        '/page1',
      ],
      [
        'http://example.com/subdir',
        '/pagesub/page1',
        '/pagesub/page1',
      ],
      [
        'http://example.com/subdir',
        'http://example.com/subdir/pagesub/page1',
        '/pagesub/page1',
      ],
    ];
  }

  /**
   * Tests toAbsolute() method.
   *
   * @dataProvider dataProviderToAbsolute
   * @covers \Drupal\minisite\UrlBag::toAbsolute
   */
  public function testToAbsolute($base_url, $url, $expected, $expect_exception = FALSE) {
    if ($expect_exception) {
      $this->expectException(UrlBagException::class);
    }
    $actual = $this->callProtectedMethod(UrlBag::class, 'toAbsolute', [$url, $base_url]);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testToAbsolute.
   */
  public function dataProviderToAbsolute() {
    return [
      [
        'http://example.com',
        'http://example.com',
        'http://example.com',
      ],
      [
        'http://example.com',
        'http://example.com/page1.html',
        'http://example.com/page1.html',
      ],
      [
        'http://example.com',
        'http://otherdomain.com/page1.html',
        'http://otherdomain.com/page1.html',
      ],
      [
        'http://example.com',
        'page1.html',
        'http://example.com/page1.html',
      ],
      [
        'http://example.com',
        'subpath/page1.html',
        'http://example.com/subpath/page1.html',
      ],
      [
        'http://example.com',
        '/page1.html',
        'http://example.com/page1.html',
      ],
      [
        'http://example.com',
        '/subpath/page1.html',
        'http://example.com/subpath/page1.html',
      ],
    ];
  }

  /**
   * Tests getUriPart() method.
   *
   * @dataProvider dataProviderGetUriPart
   * @covers \Drupal\minisite\UrlBag::getUriPart
   */
  public function testGetUriPart($uri, $part_name, $expected, $expect_exception = FALSE) {
    if ($expect_exception) {
      $this->expectException(UrlBagException::class);
    }
    $actual = $this->callProtectedMethod(UrlBag::class, 'getUriPart', [$uri, $part_name]);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for getUriPart.
   */
  public function dataProviderGetUriPart() {
    return [
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/subpath/file',
        UrlBag::URI_PART_ASSET_DIR,
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5',
        UrlBag::URI_PART_ASSET_DIR,
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a',
        UrlBag::URI_PART_ASSET_DIR,
        NULL,
        TRUE,
      ],

      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/subpath/file',
        UrlBag::URI_PART_ROOT_ARCHIVE_DIR,
        'subpath',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/subpath/file/',
        UrlBag::URI_PART_ROOT_ARCHIVE_DIR,
        'subpath',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a/subpath/file/',
        UrlBag::URI_PART_ROOT_ARCHIVE_DIR,
        NULL,
        TRUE,
      ],

      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/subpath/file',
        UrlBag::URI_PART_PATH_IN_ARCHIVE,
        'file',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/subpath/second/file',
        UrlBag::URI_PART_PATH_IN_ARCHIVE,
        'second/file',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/subpath/second/third/file',
        UrlBag::URI_PART_PATH_IN_ARCHIVE,
        'second/third/file',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/subpath/file/',
        UrlBag::URI_PART_PATH_IN_ARCHIVE,
        'file',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a/subpath/file/',
        UrlBag::URI_PART_PATH_IN_ARCHIVE,
        NULL,
        TRUE,
      ],

      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/subpath/file.txt',
        UrlBag::URI_PART_BASENAME,
        'file.txt',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/subpath/file/',
        UrlBag::URI_PART_BASENAME,
        'file',
      ],
      [
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a/subpath/file/',
        UrlBag::URI_PART_BASENAME,
        NULL,
        TRUE,
      ],
    ];
  }

  /**
   * Test getter functions.
   */
  public function testGetters() {
    $bag = new UrlBag('public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/file.txt', 'http://example.com');

    $this->assertEquals('http://example.com', $bag->getBaseUrl());
    $this->assertEquals('public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/file.txt', $bag->getUri());
    $this->assertEquals('rootpath', $bag->getRootDir());
    $this->assertEquals('subpath/file.txt', $bag->getPathInArchive());
    // Relative URL will be within VFS.
    $this->assertContains('files/minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/file.txt', $bag->getUrl());
    // Can only test absolute URLs with partial matching.
    $this->assertContains('files/minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/file.txt', $bag->getUrlAbsolute());

    $this->assertNull($bag->getAlias());
    $this->assertNull($bag->getAliasAbsolute());
    $this->assertNull($bag->getParentAlias());
    $this->assertNull($bag->getParentAliasAbsolute());

    // With parent alias.
    $bag->setParentAlias('/parent/alias');
    $this->assertEquals('/parent/alias/rootpath/subpath/file.txt', $bag->getAlias());
    $this->assertContains('/parent/alias/rootpath/subpath/file.txt', $bag->getAliasAbsolute());
    $this->assertEquals('/parent/alias', $bag->getParentAlias());
    $this->assertContains('/parent/alias', $bag->getParentAliasAbsolute());
    $this->assertNotContains('rootpath/subpath/file.txt', $bag->getParentAlias());
    $this->assertNotContains('rootpath/subpath/file.txt', $bag->getParentAliasAbsolute());

    // Same as above, but with alias set. Need a new bag to test cleanly.
    $bag = new UrlBag('public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/file.txt', 'http://example.com');

    // With alias autodiscovery.
    $bag->setAlias('/parent/alias/rootpath/subpath/file.txt');
    $this->assertEquals('/parent/alias/rootpath/subpath/file.txt', $bag->getAlias());
    $this->assertContains('/parent/alias/rootpath/subpath/file.txt', $bag->getAliasAbsolute());
    $this->assertEquals('/parent/alias', $bag->getParentAlias());
    $this->assertContains('/parent/alias', $bag->getParentAliasAbsolute());
    $this->assertNotContains('rootpath/subpath/file.txt', $bag->getParentAlias());
    $this->assertNotContains('rootpath/subpath/file.txt', $bag->getParentAliasAbsolute());
  }

}
