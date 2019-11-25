<?php

namespace Drupal\minisite\Exception;

/**
 * Class InvalidContentArchiveException.
 *
 * Thrown when archive content is invalid.
 *
 * @package Drupal\minisite\Exception
 */
class InvalidContentArchiveException extends ArchiveException {

  /**
   * InvalidContentArchiveException constructor.
   *
   * @param array $errors
   *   Array of error messages encountered during archive content validation.
   */
  public function __construct(array $errors) {
    parent::__construct(sprintf('Archive has invalid content: %s', implode(PHP_EOL, $errors)));
  }

}
