<?php

namespace Drupal\minisite;

use Drupal\file\FileInterface;

/**
 * Interface MinisiteInterface.
 *
 * @package Drupal\minisite
 */
interface MinisiteInterface {

  /**
   * Directory to upload Minisite archive.
   */
  const ARCHIVE_UPLOAD_DIR = 'minisite' . DIRECTORY_SEPARATOR . 'upload';

  /**
   * Directory for assets files, where uploaded archives will be extracted to.
   */
  const ASSET_DIR = 'minisite' . DIRECTORY_SEPARATOR . 'static';

  /**
   * Default allowed extensions.
   */
  const ALLOWED_EXTENSIONS = 'html htm js css png jpg gif svg pdf doc docx ppt pptx xls xlsx tif xml txt woff woff2 ttf eot';

  /**
   * Ext extensions that can never be allowed.
   */
  const DENIED_EXTENSIONS = 'exe scr bmp';

  /**
   * Archive extensions supported by the current implementation.
   */
  const SUPPORTED_ARCHIVE_EXTENSIONS = 'zip tar';

  /**
   * Get asset entry point URI.
   *
   * @return string
   *   String URI of the entry point for the minisite.
   */
  public function getIndexAssetUri();

  /**
   * Validate archive.
   *
   * Can be used at early stages before Minisite instance is created (i.e. when
   * uploading a file) to validate the archive.
   *
   * @param \Drupal\file\FileInterface $file
   *   The archive file to validate.
   * @param string $contentExtensions
   *   Space-separated string list of allowed file extensions in the archive.
   *
   * @throws \Drupal\minisite\Exception\ArchiveException
   *   Throws one of the descendants of this exception based on validation
   *   failures.
   */
  public static function validateArchive(FileInterface $file, $contentExtensions);

  /**
   * Path to the common directory with uploaded Minisite archive files.
   */
  public static function getCommonArchiveDir();

  /**
   * Path to the common directory with extracted Minisite archive files.
   */
  public static function getCommonAssetDir();

  /**
   * Get supported archive extensions.
   *
   * @return array
   *   Array of supported archive extensions.
   */
  public static function supportedArchiveExtensions();

}
