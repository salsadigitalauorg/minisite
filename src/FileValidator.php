<?php

namespace Drupal\minisite;

use Drupal\Component\Utility\NestedArray;
use Drupal\minisite\Exception\InvalidExtensionValidatorException;
use Drupal\minisite\Exception\InvalidPathLengthValidatorException;

/**
 * Class FileValidator.
 *
 * @package Drupal\minisite
 */
class FileValidator {

  /**
   * Validate that file has one of the provided extensions.
   *
   * @param string $filename
   *   The filename to validate.
   * @param array $extensions
   *   Array of extensions to validate.
   *
   * @throws \Drupal\minisite\Exception\InvalidExtensionValidatorException
   *   If filename does not have a valid extension from provided list.
   */
  public static function validateFileExtension($filename, array $extensions) {
    if (empty($extensions)) {
      return;
    }

    // Ignore folders.
    if (substr($filename, -1) == '/') {
      return;
    }

    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote(implode(' ', $extensions))) . ')$/i';
    if (!preg_match($regex, $filename)) {
      throw new InvalidExtensionValidatorException($filename);
    }
  }

  /**
   * Validate that full file path is under allowed characters length.
   *
   * @param string $path
   *   The file path to validate.
   *
   * @throws \Drupal\minisite\Exception\InvalidPathLengthValidatorException
   *   If file path length is above the allowed limit.
   */
  public static function validateFilePathLength($path) {
    $allowed_length =
      // Total alias length.
      2048 -
      // Public file stream wrapper.
      strlen('public://') -
      // Default static files location.
      // @todo: Add support for getting this value from the field settings.
      strlen(MinisiteInterface::ASSET_DIR) -
      // URL delimiter.
      1 -
      // UUID length.
      36 -
      // URL delimiter.
      1;
    if (strlen($path) > $allowed_length) {
      throw new InvalidPathLengthValidatorException($path, $allowed_length);
    }
  }

  /**
   * Normalise extensions to convert them to array.
   *
   * @param string $extensions
   *   String of space and/or comma separated list of extensions.
   *
   * @return array
   *   Extension list convert to an array.
   */
  public static function normaliseExtensions($extensions) {
    if (is_array($extensions)) {
      return $extensions;
    }

    $extensions = str_replace([', ', ',', ' '], ' ', $extensions);
    $extensions = str_replace(' ', ', ', $extensions);

    return array_values(array_filter(explode(', ', $extensions)));
  }

  /**
   * Convert a list of files and directories to a tree.
   *
   * Note that this method will normalise output by filling-in directory levels
   * missing from the list.
   *
   * @code
   * $files = [
   *  'dir1/',
   *  'file1.txt',
   *  'dir2/file21.txt'
   *  'dir2/file22.txt'
   *  'dir3/dir31/file311.txt'
   * ];
   *
   * $tree = filesToTree($files);
   *
   * $tree = [
   *   'dir1/' => [
   *     '.' => 'dir1/'
   *   ],
   *   'file1.txt' => 'file1.txt',
   *   'dir2/' => [
   *     '.' => 'dir2/'
   *     'file21.txt' => 'dir2/file21.txt'
   *     'file22.txt' => 'dir2/file22.txt'
   *   ],
   *   'dir3/' => [
   *     '.' => 'dir3/'
   *     'dir31/' => [
   *        '.' => 'dir3/dir31/'
   *        'file311.txt' => 'dir3/dir31/file311.txt'
   *      ]
   *   ]
   * ];
   * @endcode
   *
   * @return array
   *   Files and directories tree, keyed by list of files within the directory
   *   on the current level and values are full originally provided paths.
   *   Directories entries have themselves listed as children with special
   *   key '.' that have a value of the directory path.
   */
  public static function filesToTree(array $files) {
    $tree = [];

    foreach ($files as $file_path) {
      $parts = explode('/', $file_path);

      // Directory would end with '/' and have an empty last element.
      if (empty(end($parts))) {
        // Replace last empty element with dot (.) to use it as a dir name
        // in the current dir.
        $parts = array_slice($parts, 0, -1);
        $parts[] = '.';
      }

      // Set the value of the path's parents to make sure that every parent
      // directory is listed.
      for ($i = 0; $i < count($parts) - 1; $i++) {
        $parent_parts = array_slice($parts, 0, $i + 1);
        $key_exists = FALSE;
        $existing_value = NestedArray::getValue($tree, $parent_parts, $key_exists);
        // Check if the value was not previously set as file and now set as
        // directory.
        if ($key_exists && !is_array($existing_value)) {
          throw new \RuntimeException('Invalid file list provided');
        }
        NestedArray::setValue($tree, array_merge($parent_parts, ['.']), implode('/', $parent_parts) . '/');
      }

      // Check if the value was not previously set as directory and now set as
      // file.
      $key_exists = FALSE;
      $existing_value = NestedArray::getValue($tree, $parts, $key_exists);
      if (end($parts) != '.' && $key_exists && is_array($existing_value)) {
        throw new \RuntimeException('Invalid file list provided');
      }

      // Set the value of the path at the provided hierarchy.
      NestedArray::setValue($tree, $parts, $file_path);
    }

    return $tree;
  }

}
