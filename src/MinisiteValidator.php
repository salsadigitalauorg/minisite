<?php

namespace Drupal\minisite;

use Drupal\Component\Utility\NestedArray;
use Drupal\minisite\Exception\InvalidContentArchiveException;
use Drupal\minisite\Exception\InvalidExtensionValidatorException;

/**
 * Class MinisiteValidator.
 *
 * @package Drupal\minisite
 */
class MinisiteValidator {

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
   * Validate files.
   *
   * @param array $files
   *   Array of files to validate.
   * @param string $extensions
   *   String list of allowed extensions to validate against.
   *
   * @throws \Drupal\minisite\Exception\InvalidContentArchiveException
   *   When there is one or more validation errors.
   */
  public static function validateFiles(array $files, $extensions) {
    $extensions = self::normaliseExtensions($extensions);

    $tree = self::filesToTree($files);

    $root_files = array_keys($tree);

    // Check that a single top directory is always exists and it's only one.
    if (count($root_files) !== 1 || !is_array($tree[$root_files[0]])) {
      throw new InvalidContentArchiveException(['A single top level directory is expected.']);
    }

    // Check that entry point file exists.
    $top_folder = $root_files[0];
    $top_level = $tree[$top_folder];
    if (!array_key_exists(MinisiteAsset::INDEX_FILE, $top_level)) {
      throw new InvalidContentArchiveException([sprintf('Missing required %s file.', MinisiteAsset::INDEX_FILE)]);
    }

    // Check that all files have only allowed extensions.
    $errors = [];
    foreach ($files as $file) {
      try {
        static::validateFileExtension($file, $extensions);
      }
      catch (InvalidExtensionValidatorException $exception) {
        $errors[] = $exception->getMessage();
      }
    }

    if (count($errors) > 0) {
      throw new InvalidContentArchiveException($errors);
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
   * @return array
   *   Files and directories tree, keyed by directories.
   */
  protected static function filesToTree(array $files) {
    $tree = [];

    foreach ($files as $file_path) {
      $parts = explode('/', $file_path);

      if (substr($file_path, -1) === '/') {
        $parts = array_slice($parts, 0, -1);
        NestedArray::setValue($tree, $parts, ['.' => $file_path]);
      }
      else {
        NestedArray::setValue($tree, $parts, $file_path);
      }
    }

    return $tree;
  }

}
