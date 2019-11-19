<?php

namespace Drupal\minisite\Exception;

/**
 * Class InvalidExtensionValidatorException.
 *
 * Thrown when invalid extension encountered by validator.
 *
 * @package Drupal\minisite\Exception
 */
class InvalidExtensionValidatorException extends \Exception {

  /**
   * InvalidExtensionValidatorException constructor.
   *
   * @param string $filename
   *   The file name that was validated.
   */
  public function __construct($filename) {
    parent::__construct(sprintf('File %s has invalid extension.', $filename));
  }

}
