<?php

namespace Drupal\minisite\Exception;

/**
 * Class InvalidPathLengthValidatorException.
 *
 * Thrown when invalid path length encountered by validator.
 *
 * @package Drupal\minisite\Exception
 */
class InvalidPathLengthValidatorException extends \Exception {

  /**
   * InvalidPathLengthValidatorException constructor.
   *
   * @param string $filename
   *   The file name that was validated.
   * @param int $length
   *   The allowed characters length.
   */
  public function __construct($filename, $length) {
    parent::__construct(sprintf('File "%s" path within the archive should be under %s characters in length.', $filename, $length));
  }

}
