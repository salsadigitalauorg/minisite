<?php

namespace Drupal\minisite;

use Drupal\Core\Field\FieldItemListInterface;
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
  const ALLOWED_EXTENSIONS = 'html htm js css png jpg gif svg pdf doc docx ppt pptx xls xlsx tif xml txt woff woff2 ttf eot ico';

  /**
   * Extensions that can never be allowed.
   */
  const DENIED_EXTENSIONS = 'exe scr bmp';

  /**
   * Archive extensions supported by the current implementation.
   */
  const SUPPORTED_ARCHIVE_EXTENSIONS = 'zip tar';

  /**
   * Create an instance of this class from the field items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The existing field items.
   *
   * @return \Drupal\minisite\Minisite
   *   An instance of this class.
   */
  public static function createInstance(FieldItemListInterface $items);

  /**
   * Get archive file.
   *
   * @return \Drupal\file\FileInterface
   *   Archive file used to instantiate this minisite.
   */
  public function getArchiveFile();

  /**
   * Set archive file.
   *
   * @param \Drupal\file\FileInterface $file
   *   Already uploaded archive file object to set.
   */
  public function setArchiveFile(FileInterface $file);

  /**
   * Get description.
   *
   * @return string
   *   The description string.
   */
  public function getDescription();

  /**
   * Set description.
   *
   * @param string $description
   *   The description to set.
   */
  public function setDescription($description);

  /**
   * Get asset entry point URL.
   *
   * @return string
   *   URL as a relative path to the asset or an alias.
   *
   * @see \Drupal\minisite\AssetInterface::INDEX_FILE
   */
  public function getIndexAssetUrl();

  /**
   * Get asset entry point URI.
   *
   * @return string
   *   URI of the file which is an entry point for the minisite.
   *
   * @see \Drupal\minisite\AssetInterface::INDEX_FILE
   */
  public function getIndexAssetUri();

  /**
   * Save minisite.
   *
   * Note that assets would already be extracted.
   */
  public function save();

  /**
   * Delete minisite.
   */
  public function delete();

  /**
   * Validate archive.
   *
   * Can be used at early stages before Minisite instance is created (i.e. when
   * uploading a file) to validate the archive.
   *
   * @param \Drupal\file\FileInterface $file
   *   The archive file to validate.
   * @param string $content_extensions
   *   Space-separated string list of allowed file extensions in the archive.
   *
   * @throws \Drupal\minisite\Exception\ArchiveException
   *   Throws one of the descendants of this exception based on validation
   *   failures.
   */
  public static function validateArchive(FileInterface $file, $content_extensions);

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

  /**
   * Get cache tags for this site.
   *
   * @return string[]
   *   Array of cache tags.
   */
  public function getCacheTags();

  /**
   * Get the path to the asset directory.
   *
   * @return string
   *   The path to the assets directory.
   */
  public function getAssetDirectory();

}
