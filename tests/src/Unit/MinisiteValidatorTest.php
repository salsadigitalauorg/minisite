<?php

namespace Drupal\Tests\minisite\Unit;

use Drupal\minisite\Exception\InvalidContentArchiveException;
use Drupal\minisite\Exception\InvalidExtensionValidatorException;
use Drupal\minisite\MinisiteAsset;
use Drupal\minisite\MinisiteValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Class MinisiteValidatorTest.
 *
 * Tests validator.
 *
 * @group minisite
 *
 * @package Drupal\testmode\Tests
 */
class MinisiteValidatorTest extends UnitTestCase {

  /**
   * Test validateFileExtension() method.
   *
   * @dataProvider dataProviderValidateFileExtension
   * @covers \Drupal\minisite\MinisiteValidator::validateFileExtension
   */
  public function testValidateFileExtension($filename, $extensions) {
    $this->expectException(InvalidExtensionValidatorException::class);
    $this->expectExceptionMessage(sprintf('File %s has invalid extension.', $filename));
    MinisiteValidator::validateFileExtension($filename, $extensions);
  }

  /**
   * Data provider for testValidateFileExtension.
   */
  public function dataProviderValidateFileExtension() {
    return [
      ['file.txt', ['ext']],
      ['file.txt', ['ext', 'ext2']],
      ['file', ['ext', 'ext2']],
      ['file.ext.txt', ['ext', 'ext2']],
    ];
  }

  /**
   * Test validateFiles() method.
   *
   * @dataProvider dataProviderValidateFiles
   * @covers \Drupal\minisite\MinisiteValidator::validateFiles
   */
  public function testValidateFiles($files, $extensions, $message) {
    $this->expectException(InvalidContentArchiveException::class);
    $this->expectExceptionMessage($message);
    MinisiteValidator::validateFiles($files, $extensions);
  }

  /**
   * Data provider for testValidateFiles.
   */
  public function dataProviderValidateFiles() {
    return [
      [
        ['file.txt'], ['ext'], 'A single top level directory is expected.',
      ],
      [
        ['file.txt', 'dir1/file.txt'], ['ext'], 'A single top level directory is expected.',
      ],
      [
        ['dir1/file.txt'], ['ext'], sprintf('Missing required %s file.', MinisiteAsset::INDEX_FILE),
      ],
      [
        ['dir1/', 'dir1/file.txt'], ['ext'], sprintf('Missing required %s file.', MinisiteAsset::INDEX_FILE),
      ],
      [
        ['dir1/' . MinisiteAsset::INDEX_FILE, 'dir1/file.txt'], ['html', 'ext'], 'Archive has invalid content: File dir1/file.txt has invalid extension.',
      ],
      [
        ['dir1/' . MinisiteAsset::INDEX_FILE, 'dir1/file.txt', 'dir1/file2.txt'], ['html', 'ext'], 'Archive has invalid content: File dir1/file.txt has invalid extension.' . PHP_EOL . 'File dir1/file2.txt has invalid extension.',
      ],
    ];
  }

  /**
   * Test normaliseExtensions() method.
   *
   * @dataProvider dataProviderNormaliseExtensions
   * @covers \Drupal\minisite\MinisiteValidator::normaliseExtensions
   */
  public function testNormaliseExtensions($extensions, $expected) {
    $actual = MinisiteValidator::normaliseExtensions($extensions);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testNormaliseExtensions.
   */
  public function dataProviderNormaliseExtensions() {
    return [
      [[], []],
      ['', []],
      [' ', []],
      [', ', []],
      [' , ', []],
      ['  , ', []],
      ['  ,', []],

      ['a b c', ['a', 'b', 'c']],
      ['a,b,c', ['a', 'b', 'c']],
      ['a, b, c', ['a', 'b', 'c']],
      ['a, b , c', ['a', 'b', 'c']],
      ['a, b c', ['a', 'b', 'c']],
      ['   a,    b   c', ['a', 'b', 'c']],
    ];
  }

}
