<?php

namespace Drupal\Tests\minisite\Unit;

use Drupal\minisite\ArchiveValidator;
use Drupal\minisite\AssetInterface;
use Drupal\minisite\Exception\InvalidContentArchiveException;
use Drupal\Tests\UnitTestCase;

/**
 * Class ArchiveValidatorTest.
 *
 * Tests archive validator.
 *
 * @group minisite
 *
 * @package Drupal\testmode\Tests
 */
class ArchiveValidatorTest extends UnitTestCase {

  /**
   * Test validateFiles() method.
   *
   * @dataProvider dataProviderValidate
   * @covers \Drupal\minisite\ArchiveValidator::validate
   */
  public function testValidate($files, $extensions, $message) {
    $this->expectException(InvalidContentArchiveException::class);
    $this->expectExceptionMessage($message);
    ArchiveValidator::validate($files, $extensions);
  }

  /**
   * Data provider for testValidateFiles.
   */
  public function dataProviderValidate() {
    return [
      [
        [
          'file.txt',
        ],
        ['ext'],
        'A single top level directory is expected.',
      ],
      [
        [
          'file.txt',
          'dir1/file.txt',
        ],
        ['ext'],
        'A single top level directory is expected.',
      ],
      [
        [
          'dir1/',
          'dir2/',
          'dir1/file.txt',
        ],
        ['ext'],
        'A single top level directory is expected.',
      ],
      [
        [
          'dir1/file.txt',
        ],
        ['ext'],
        sprintf('Missing required %s file.', AssetInterface::INDEX_FILE),
      ],
      [
        [
          'dir1/',
          'dir1/file.txt',
        ],
        ['ext'],
        sprintf('Missing required %s file.', AssetInterface::INDEX_FILE),
      ],
      [
        [
          'dir1/' . AssetInterface::INDEX_FILE,
          'dir1/file.txt',
        ],
        ['html', 'ext'],
        'Archive has invalid content: File dir1/file.txt has invalid extension.',
      ],
      [
        [
          'dir1/' . AssetInterface::INDEX_FILE,
          'dir1/file.txt', 'dir1/file2.txt',
        ],
        ['html', 'ext'],
        'Archive has invalid content: File dir1/file.txt has invalid extension.' . PHP_EOL . 'File dir1/file2.txt has invalid extension.',
      ],

      [
        [
          'dir1/' . AssetInterface::INDEX_FILE,
          'dir1/file.html', 'dir1/' . str_repeat('a', 2048) . '/file2.html',
        ],
        ['html'],
        'Archive has invalid content: File "dir1/' . str_repeat('a', 2048) . '/file2.html" path within the archive should be under 1986 characters in length.',
      ],

      // Special case testing for allowed root-level directories.
      // If the allowed root-level directory not correctly excluded - a
      // different exception will be thrown.
      [
        [
          '__MACOSX/',
          'dir1/' . AssetInterface::INDEX_FILE,
          'dir1/file.txt',
        ],
        ['html', 'ext'],
        'Archive has invalid content: File dir1/file.txt has invalid extension.',
      ],
    ];
  }

}
