<?php

namespace Drupal\minisite;

use Drupal\minisite\Exception\InvalidContentArchiveException;
use Drupal\minisite\Exception\InvalidExtensionValidatorException;

/**
 * Class ArchiveValidator.
 *
 * @package Drupal\minisite
 */
class ArchiveValidator {

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
  public static function validate(array $files, $extensions) {
    $extensions = FileValidator::normaliseExtensions($extensions);

    $tree = FileValidator::filesToTree($files);

    $root_files = array_keys($tree);

    // Remove any expected root directories.
    $root_files = array_values(array_diff($root_files, self::allowedRootDirectories()));

    // Check that a single top directory is always exists and it's only one.
    if (count($root_files) !== 1 || !is_array($tree[$root_files[0]])) {
      throw new InvalidContentArchiveException(['A single top level directory is expected.']);
    }

    // Check that entry point file exists.
    $top_folder = $root_files[0];
    $top_level = $tree[$top_folder];
    if (!array_key_exists(AssetInterface::INDEX_FILE, $top_level)) {
      throw new InvalidContentArchiveException([sprintf('Missing required %s file.', AssetInterface::INDEX_FILE)]);
    }

    // Check that all files have only allowed extensions.
    $errors = [];
    foreach ($files as $file) {
      try {
        FileValidator::validateFileExtension($file, $extensions);
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
   * Array of allowed root-level directories.
   *
   * @return array
   *   Array of directories.
   */
  public static function allowedRootDirectories() {
    return [
      '__MACOSX',
    ];
  }

}
