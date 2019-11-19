<?php

namespace Drupal\minisite;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\minisite\Exception\AssetException;
use Drupal\minisite\Exception\InvalidContentArchiveException;
use Drupal\minisite\Exception\InvalidFormatArchiveException;

/**
 * Class Minisite.
 *
 * Handles all interactions with Minisite data, including asset management.
 *
 * @package Drupal\minisite
 */
class Minisite implements MinisiteInterface {

  /**
   * The archive file for this minisite.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $archiveFile;

  /**
   * Array of assets for this minisite.
   *
   * @var \Drupal\minisite\MinisiteAsset[]
   */
  protected $assets = [];

  /**
   * Minisite constructor.
   *
   * @param \Drupal\file\FileInterface $archiveFile
   *   The archive file.
   * @param string $extensions
   *   String list of archive content extensions.
   */
  public function __construct(FileInterface $archiveFile, $extensions) {
    // Although archive has already been validated during upload, we still
    // need to ensure that provided file is valid to ensure data integrity.
    static::validateArchive($archiveFile, $extensions);
    $this->archiveFile = $archiveFile;
    $this->processArchive();
  }

  /**
   * Instantiate this class from the archive file.
   *
   * @param \Drupal\file\FileInterface $archiveFile
   *   The file to instantiate the class.
   * @param string $extensions
   *   String list of archive content extensions.
   *
   * @return \Drupal\minisite\Minisite
   *   An instance of this class.
   */
  public static function fromArchive(FileInterface $archiveFile, $extensions) {
    $instance = new self($archiveFile, $extensions);

    return $instance;
  }

  /**
   * Set archive file.
   *
   * @param \Drupal\file\FileInterface $file
   *   Already uploaded archive file object to set.
   */
  public function setArchiveFile(FileInterface $file) {
    $this->archiveFile = $file;
  }

  /**
   * Get archive file.
   *
   * @return \Drupal\file\FileInterface
   *   Archive file used to instantiate this minisite.
   */
  public function getArchiveFile() {
    return $this->archiveFile;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateArchive(FileInterface $file, $contentExtensions) {
    // Does it have a correct extension?
    MinisiteValidator::validateFileExtension($file->getFilename(), static::supportedArchiveExtensions());

    // Is it a valid archive?
    // File URI is different from file name is this is a file being uploaded,
    // so we must provide both to correctly instantiate the archiver.
    $archiver = self::getArchiver($file->getFileUri(), $file->getFilename());
    if (!$archiver) {
      throw new InvalidFormatArchiveException($file->getFilename());
    }

    try {
      $files = $archiver->listContents();
    }
    catch (\Exception $exception) {
      throw new InvalidFormatArchiveException($file->getFilename());
    }

    // Does it have correct structure?
    MinisiteValidator::validateFiles($files, $contentExtensions);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexAssetUri() {
    foreach ($this->assets as $asset) {
      if ($asset->isIndex()) {
        return $asset->getSource();
      }
    }

    throw new InvalidContentArchiveException(sprintf('Missing index file %s', MinisiteAsset::INDEX_FILE));
  }

  /**
   * {@inheritdoc}
   */
  public static function getCommonArchiveDir() {
    return \Drupal::config('system.file')->get('default_scheme') . '://' . MinisiteInterface::ARCHIVE_UPLOAD_DIR;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCommonAssetDir() {
    return \Drupal::config('system.file')->get('default_scheme') . '://' . MinisiteInterface::ASSET_DIR;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportedArchiveExtensions() {
    return explode(' ', MinisiteInterface::SUPPORTED_ARCHIVE_EXTENSIONS);
  }

  /**
   * Process archive.
   *
   * @throws \Drupal\minisite\Exception\UnableToExtractArchiveException
   */
  protected function processArchive() {
    $archiver = self::getArchiver($this->archiveFile->getFileUri());

    $asset_directory = $this->prepareAssetDirectory();
    $files = $archiver->listContents();
    $archiver->extract($asset_directory);

    foreach ($files as $file) {
      $asset = new MinisiteAsset($asset_directory . DIRECTORY_SEPARATOR . $file);
      $this->assets[] = $asset;
    }
  }

  /**
   * Prepare asset directory.
   *
   * @return string
   *   Prepared asset directory.
   *
   * @throws \Drupal\minisite\Exception\AssetException
   *   When unable to prepare asset.
   */
  protected function prepareAssetDirectory() {
    $suffix = $this->archiveFile->get('uuid')->value;
    $dir = self::getCommonAssetDir() . DIRECTORY_SEPARATOR . $suffix;
    if (!\Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      throw new AssetException(sprintf('Unable to prepare asset directory "%s"', $dir));
    }

    return $dir;
  }

  /**
   * Instantiate an archiver instance base on the file uir and name.
   *
   * @param string $uri
   *   URI of the file to instantiate the archiver manager with.
   * @param string $filename
   *   (optional) File name to pass to the archiver manager. if not provided,
   *   the filename will be extracted from the $uri.
   *
   * @return \Drupal\Core\Archiver\ArchiverInterface
   *   The archiver instance.
   */
  protected static function getArchiver($uri, $filename = NULL) {
    $filename_real = \Drupal::service('file_system')->realpath($uri);
    $filename = $filename ? $filename : \Drupal::service('file_system')->basename($uri);

    try {
      /** @var \Drupal\Core\Archiver\ArchiverInterface $archiver */
      $archiver = \Drupal::getContainer()->get('plugin.manager.minisite_archiver')->getInstance([
        'filepath' => $filename_real,
        'filename' => $filename,
      ]);
    }
    catch (\Exception $exception) {
      return NULL;
    }

    return $archiver;
  }

}
