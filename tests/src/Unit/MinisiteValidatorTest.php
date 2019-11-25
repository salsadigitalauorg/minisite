<?php

namespace Drupal\Tests\minisite\Unit;

use Drupal\minisite\Exception\InvalidContentArchiveException;
use Drupal\minisite\Exception\InvalidExtensionValidatorException;
use Drupal\minisite\MinisiteAsset;
use Drupal\minisite\MinisiteValidator;
use Drupal\Tests\minisite\Traits\MockHelperTrait;
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

  use MockHelperTrait;

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
   * Data provider for testValidateFileExtension().
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
   * Data provider for testNormaliseExtensions().
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

  /**
   * Test filesToTree() method.
   *
   * @dataProvider dataProviderFilesToTree
   * @covers \Drupal\minisite\MinisiteValidator::filesToTree
   */
  public function testFilesToTree($files, $expected, $expectException = FALSE) {
    if ($expectException) {
      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage('Invalid file list provided');
    }
    $actual = $this->callProtectedMethod(MinisiteValidator::class, 'filesToTree', [$files]);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testFilesToTree().
   */
  public function dataProviderFilesToTree() {
    return [
      // Root files.
      [
        [
          'file.txt',
        ],
        [
          'file.txt' => 'file.txt',
        ],
      ],

      [
        [
          'file1.txt',
          'file2.txt',
        ],
        [
          'file1.txt' => 'file1.txt',
          'file2.txt' => 'file2.txt',
        ],
      ],

      // Simple dirs.
      [
        [
          'dir1/',
        ],
        [
          'dir1' => [
            '.' => 'dir1/',
          ],
        ],
      ],
      [
        [
          'dir1/',
          'dir2/',
        ],
        [
          'dir1' => [
            '.' => 'dir1/',
          ],
          'dir2' => [
            '.' => 'dir2/',
          ],
        ],
      ],

      // Dirs with files.
      [
        [
          'dir1/file1.txt',
          'dir2/file2.txt',
        ],
        [
          'dir1' => [
            '.' => 'dir1/',
            'file1.txt' => 'dir1/file1.txt',
          ],
          'dir2' => [
            '.' => 'dir2/',
            'file2.txt' => 'dir2/file2.txt',
          ],
        ],
      ],
      [
        [
          'dir1/dir11/dir111/',
        ],
        [
          'dir1' => [
            '.' => 'dir1/',
            'dir11' => [
              '.' => 'dir1/dir11/',
              'dir111' => [
                '.' => 'dir1/dir11/dir111/',
              ],
            ],
          ],
        ],
      ],

      [
        [
          'dir1/file11.txt',
          'dir2/file21.txt',
          'dir2/file22.txt',
          'dir3/',
          'dir4/dir41/',
        ],
        [
          'dir1' => [
            '.' => 'dir1/',
            'file11.txt' => 'dir1/file11.txt',
          ],
          'dir2' => [
            '.' => 'dir2/',
            'file21.txt' => 'dir2/file21.txt',
            'file22.txt' => 'dir2/file22.txt',
          ],
          'dir3' => [
            '.' => 'dir3/',
          ],
          'dir4' => [
            '.' => 'dir4/',
            'dir41' => [
              '.' => 'dir4/dir41/',
            ],
          ],
        ],
      ],

      // Mixed dirs, files, order.
      [
        [
          'dir1/',
          'dir1/dir11/dir111/',
          'dir1/dir11/',
          'dir1/dir11/dir111/file111.txt',
          'dir1/dir11/dir111/file112.txt',
          'file1.txt',
          'file2.txt',
          'dir2/file21.txt',
          'dir2/dir21/file211.txt',
          'dir2/file22.txt',
          'dir2/dir21/file212.txt',
          'dir2/file23.txt',
        ],
        [
          'file1.txt' => 'file1.txt',
          'file2.txt' => 'file2.txt',
          'dir1' => [
            '.' => 'dir1/',
            'dir11' => [
              '.' => 'dir1/dir11/',
              'dir111' => [
                '.' => 'dir1/dir11/dir111/',
                'file111.txt' => 'dir1/dir11/dir111/file111.txt',
                'file112.txt' => 'dir1/dir11/dir111/file112.txt',
              ],
            ],
          ],
          'dir2' => [
            '.' => 'dir2/',
            'dir21' => [
              '.' => 'dir2/dir21/',
              'file211.txt' => 'dir2/dir21/file211.txt',
              'file212.txt' => 'dir2/dir21/file212.txt',
            ],
            'file21.txt' => 'dir2/file21.txt',
            'file22.txt' => 'dir2/file22.txt',
            'file23.txt' => 'dir2/file23.txt',
          ],
        ],
      ],

      // Edge case - dirs provided as files and then as dirs.
      [
        [
          'dir1',
          'dir1/file1.txt',
        ],
        [],
        TRUE,
      ],

      [
        [
          'dir1/file1.txt',
          'dir1',
        ],
        [],
        TRUE,
      ],

      [
        [
          'dir1/dir11/dir111/file111.txt',
          'dir1/dir11/dir111',
        ],
        [],
        TRUE,
      ],

      // Repeating files.
      [
        [
          'file1.txt',
          'file1.txt',
        ],
        [
          'file1.txt' => 'file1.txt',
        ],
      ],
      [
        [
          'dir1/file1.txt',
          'dir1/file1.txt',
        ],
        [
          'dir1' => [
            '.' => 'dir1/',
            'file1.txt' => 'dir1/file1.txt',
          ],
        ],
      ],
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
        sprintf('Missing required %s file.', MinisiteAsset::INDEX_FILE),
      ],
      [
        [
          'dir1/',
          'dir1/file.txt',
        ],
        ['ext'],
        sprintf('Missing required %s file.', MinisiteAsset::INDEX_FILE),
      ],
      [
        [
          'dir1/' . MinisiteAsset::INDEX_FILE,
          'dir1/file.txt',
        ],
        ['html', 'ext'],
        'Archive has invalid content: File dir1/file.txt has invalid extension.',
      ],
      [
        [
          'dir1/' . MinisiteAsset::INDEX_FILE,
          'dir1/file.txt', 'dir1/file2.txt',
        ],
        ['html', 'ext'],
        'Archive has invalid content: File dir1/file.txt has invalid extension.' . PHP_EOL . 'File dir1/file2.txt has invalid extension.',
      ],

      // Special case testing for allowed root-level directories.
      // If the allowed root-level directory not correctly excluded - a
      // different exception will be thrown.
      [
        [
          '__MACOSX/',
          'dir1/' . MinisiteAsset::INDEX_FILE,
          'dir1/file.txt',
        ],
        ['html', 'ext'],
        'Archive has invalid content: File dir1/file.txt has invalid extension.',
      ],
    ];
  }

}
