<?php

namespace Drupal\minisite\Exception;

/**
 * Class MissingArchiveException.
 *
 * Thrown when archive file is missing.
 *
 * @package Drupal\minisite\Exception
 */
class MissingArchiveException extends ArchiveException {

  /**
   * MissingArchiveException constructor.
   *
   * @param string $uri
   *   The URI of the missing archive file.
   */
  public function __construct($uri) {
    parent::__construct(sprintf('Archive file "%s" is missing.', $uri));
  }

}
