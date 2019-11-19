<?php

namespace Drupal\minisite\Exception;

/**
 * Class InvalidFormatArchiveException.
 *
 * Thrown when archive format is invalid.
 *
 * @package Drupal\minisite\Exception
 */
class InvalidFormatArchiveException extends ArchiveException {

  /**
   * InvalidFormatArchiveException constructor.
   *
   * @param string $filename
   *   The file name that was validated.
   */
  public function __construct($filename) {
    parent::__construct(sprintf('File %s is not an archive file.', $filename));
  }

}
