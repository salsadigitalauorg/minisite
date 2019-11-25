<?php

namespace Drupal\Tests\minisite\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\minisite\UrlValidator;

/**
 * Class UrlValidatorTest.
 *
 * Tests URL validator.
 *
 * @group minisite
 *
 * @package Drupal\testmode\Tests
 */
class UrlValidatorTest extends KernelTestBase {

  /**
   * Test for urlIsExternal().
   *
   * @dataProvider dataProviderUrlIsExternal
   * @covers \Drupal\minisite\UrlValidator::urlIsExternal
   */
  public function testUrlIsExternal($url, $is_external) {
    $actual = UrlValidator::urlIsExternal($url);
    $this->assertEquals($is_external, $actual);
  }

  /**
   * Data provider for testUrlIsExternal.
   */
  public function dataProviderUrlIsExternal() {
    return [
      ['http://example.com', TRUE],
      ['http://www.example.com', TRUE],
      ['//example.com', TRUE],
      ['example.com', FALSE],
      ['page', FALSE],
      ['/page', FALSE],
      ['sub/page', FALSE],
      ['/sub/page', FALSE],
      ['../sub/page', FALSE],
      ['./sub/page', FALSE],
    ];
  }

  /**
   * Test for urlIsRoot().
   *
   * @dataProvider dataProviderUrlIsRoot
   * @covers \Drupal\minisite\UrlValidator::urlIsRoot
   */
  public function testUrlIsRoot($url, $is_root) {
    $actual = UrlValidator::urlIsRoot($url);
    $this->assertEquals($is_root, $actual);
  }

  /**
   * Data provider for testUrlIsRoot.
   */
  public function dataProviderUrlIsRoot() {
    return [
      ['http://example.com', FALSE],
      ['http://www.example.com', FALSE],
      ['//example.com', FALSE],
      ['example.com', FALSE],
      ['page', FALSE],
      ['/page', TRUE],
      ['sub/page', FALSE],
      ['/sub/page', TRUE],
      ['./page', TRUE],
      ['./sub/page', TRUE],
      ['../sub/page', FALSE],
      ['../../sub/page', FALSE],
    ];
  }

  /**
   * Tests for urlIsRelative().
   *
   * @dataProvider dataProviderUrlIsRelative
   * @covers \Drupal\minisite\UrlValidator::urlIsRelative
   */
  public function testUrlIsRelative($url, $is_relative) {
    $actual = UrlValidator::urlIsRelative($url);
    $this->assertEquals($is_relative, $actual);
  }

  /**
   * Data provider for testUrlIsRelative.
   */
  public function dataProviderUrlIsRelative() {
    return [
      ['http://example.com', FALSE],
      ['http://www.example.com', FALSE],
      ['//example.com', FALSE],
      ['example.com', FALSE],
      ['page', FALSE],
      ['/page', FALSE],
      ['sub/page', FALSE],
      ['/sub/page', FALSE],
      ['./page', FALSE],
      ['./sub/page', FALSE],
      ['../sub/page', TRUE],
      ['../../sub/page', TRUE],
    ];
  }

  /**
   * Tests for urlIsIndex().
   *
   * @dataProvider dataProviderUrlIsIndex
   * @covers \Drupal\minisite\UrlValidator::urlIsIndex
   */
  public function testUrlIsIndex($url, $index_file, $is_index) {
    if ($index_file) {
      $actual = UrlValidator::urlIsIndex($url, $index_file);
    }
    else {
      $actual = UrlValidator::urlIsIndex($url);
    }
    $this->assertEquals($is_index, $actual);
  }

  /**
   * Data provider for testUrlIsIndex.
   */
  public function dataProviderUrlIsIndex() {
    return [
      ['http://example.com', NULL, FALSE],
      ['http://www.example.com', NULL, FALSE],
      ['//example.com', NULL, FALSE],
      ['example.com', NULL, FALSE],
      ['page', NULL, FALSE],
      ['/page', NULL, FALSE],
      ['sub/page', NULL, FALSE],
      ['/sub/page', NULL, FALSE],
      ['./page', NULL, FALSE],
      ['./sub/page', NULL, FALSE],
      ['../sub/page', NULL, FALSE],
      ['../../sub/page', NULL, FALSE],

      ['http://example.com/index.html', NULL, TRUE],
      ['//example.com/index.html', NULL, TRUE],
      ['index.html', NULL, TRUE],
      ['/index.html', NULL, TRUE],
      ['sub/index.html', NULL, TRUE],
      ['/sub/index.html', NULL, TRUE],
      ['./index.html', NULL, TRUE],
      ['./sub/index.html', NULL, TRUE],
      ['../sub/index.html', NULL, TRUE],
      ['../../sub/index.html', NULL, TRUE],

      ['index2.html', 'index2.html', TRUE],
    ];
  }

  /**
   * Tests for rootToRelative().
   *
   * @dataProvider dataProviderRootToRelative
   * @covers \Drupal\minisite\UrlValidator::rootToRelative
   */
  public function testRootToRelative($url, $prefix, $expected) {
    $actual = UrlValidator::rootToRelative($url, $prefix);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testRootToRelative.
   */
  public function dataProviderRootToRelative() {
    return [
      ['http://example.com/file', NULL, 'http://example.com/file'],
      ['file', NULL, 'file'],

      ['/file', NULL, 'file'],
      ['./file', NULL, 'file'],
      ['./file', 'prefix', 'prefix/file'],
      ['/file', 'prefix', 'prefix/file'],
      ['/file', '/prefix', '/prefix/file'],
      ['./file', 'prefix/sub', 'prefix/sub/file'],
      ['/file', 'prefix/sub', 'prefix/sub/file'],
      ['/file', '/prefix/sub', '/prefix/sub/file'],
    ];
  }

  /**
   * Tests for relativeToRoot.
   *
   * @dataProvider dataProviderRelativeToRoot
   * @covers \Drupal\minisite\UrlValidator::relativeToRoot
   */
  public function testRelativeToRoot($url, $prefix, $expected) {
    $actual = UrlValidator::relativeToRoot($url, $prefix);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testRelativeToRoot.
   */
  public function dataProviderRelativeToRoot() {
    return [
      ['http://example.com/file', NULL, 'http://example.com/file'],
      ['file', NULL, '/file'],
      ['/file', NULL, '/file'],
      ['./file', NULL, '/file'],
      ['./file', 'prefix', '/prefix/file'],
      ['/file', 'prefix', '/prefix/file'],
      ['/file', '/prefix', '/prefix/file'],
      ['./file', 'prefix/sub', '/prefix/sub/file'],
      ['/file', 'prefix/sub', '/prefix/sub/file'],
      ['/file', '/prefix/sub', '/prefix/sub/file'],

      ['../file', NULL, '/file'],
      ['../../file', NULL, '/file'],
      ['../../../file', NULL, '/file'],

      ['../file', 'prefix', '/prefix/file'],
      ['../../file', 'prefix', '/prefix/file'],
      ['../../../file', 'prefix', '/prefix/file'],
      ['../../../file', '/prefix', '/prefix/file'],
      ['../../../file', 'prefix/sub/', '/prefix/sub/file'],
    ];
  }

}
