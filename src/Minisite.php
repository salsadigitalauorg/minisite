<?php

namespace Drupal\minisite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\minisite\Exception\ArchiveException;
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
   * The minisite description.
   *
   * @var string
   */
  protected $description;

  /**
   * The parent entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The parent entity bundle.
   *
   * @var string
   */
  protected $entityBundle;

  /**
   * The parent entity id.
   *
   * @var int
   */
  protected $entityId;

  /**
   * The parent entity revision.
   *
   * @var int
   */
  protected $entityRid;

  /**
   * The parent entity language.
   *
   * @var string
   */
  protected $entityLanguage;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Alias prefix taken from the parent entity.
   *
   * @var string
   */
  protected $aliasPrefix;

  /**
   * Assets container.
   *
   * @var \Drupal\minisite\AssetContainer
   *
   * @todo: Remove this container and use assets directly.
   */
  protected $assetContainer;

  /**
   * Minisite constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Parent entity where the minisite field is located.
   * @param string $field_name
   *   The minisite field name.
   * @param \Drupal\file\FileInterface $archive_file
   *   The archive managed file.
   */
  public function __construct(EntityInterface $entity, $field_name, FileInterface $archive_file) {
    $this->entityType = $entity->getEntityTypeId();
    $this->entityBundle = $entity->bundle();
    $this->entityId = $entity->id();
    $this->entityLanguage = $entity->language()->getId();
    $this->fieldName = $field_name;
    $this->archiveFile = $archive_file;

    if ($entity->getEntityType()->isRevisionable()) {
      $this->entityRid = $entity->getRevisionId();
    }

    $this->assetContainer = new AssetContainer();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(FieldItemListInterface $items) {
    $archive_file = $items->entity;

    if (!$archive_file) {
      return NULL;
    }

    $field_definition = $items->getFieldDefinition();

    try {
      static::validateArchive($archive_file, $field_definition->getSetting('minisite_extensions'));

      $entity = $items->getEntity();
      $instance = new self($entity, $field_definition->getName(), $archive_file);

      if (!empty($items->get(0))) {
        $instance->setDescription($items->get(0)->description);

        // Set alias for all assets of this minisite if it was selected for this
        // field on this entity and the entity has a path alias set.
        $value = $items->get(0)->getValue();
        $entity_alias = self::getEntityPathAlias($entity);
        if (!empty($value['options']['alias_status']) && !empty($entity_alias)) {
          $instance->setAlias($entity_alias);
        }
      }
    }
    catch (ArchiveException $exception) {
      $instance = NULL;
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFile() {
    return $this->archiveFile;
  }

  /**
   * {@inheritdoc}
   */
  public function setArchiveFile(FileInterface $file) {
    $this->archiveFile = $file;
  }

  /**
   * {@inheritdoc}
   */
  public function setAlias($alias_prefix) {
    $this->aliasPrefix = $alias_prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexAssetUrl() {
    $asset = $this->assetContainer->getIndexAsset();

    if (empty($asset)) {
      throw new InvalidContentArchiveException([sprintf('Missing index file %s', AssetInterface::INDEX_FILE)]);
    }

    return $asset->getUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexAssetUri() {
    $uri = $this->assetContainer->getIndexAssetUri();

    if (empty($uri)) {
      throw new InvalidContentArchiveException([sprintf('Missing index file %s', AssetInterface::INDEX_FILE)]);
    }

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function processArchive() {
    $asset_directory = $this->prepareAssetDirectory();

    $files = file_scan_directory($asset_directory, '/.*/');

    if (!$files) {
      $archiver = self::getArchiver($this->archiveFile->getFileUri());
      $archiver->listContents();
      $archiver->extract($asset_directory);
      $files = file_scan_directory($asset_directory, '/.*/');
    }

    foreach (array_keys($files) as $file_uri) {
      $this->assetContainer->add(
        $this->entityType,
        $this->entityBundle,
        $this->entityId,
        $this->entityRid,
        $this->entityLanguage,
        $this->fieldName,
        // Full uri to a file, e.g.
        // @code
        // public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/ms/page1.html
        // @endcode
        $file_uri
      );
    }

    if (!empty($this->aliasPrefix)) {
      $this->assetContainer->updateAliases($this->aliasPrefix);
    }

    // @todo: Do not save on each init.
    $this->assetContainer->saveAll();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->assetContainer->deleteAll();
    $this->archiveFile->delete();
    $this->archiveFile = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateArchive(FileInterface $file, $content_extensions) {
    // Does it have a correct extension?
    FileValidator::validateFileExtension($file->getFilename(), static::supportedArchiveExtensions());

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
    ArchiveValidator::validate($files, $content_extensions);
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
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Currently using only archive file's cache tags.
    return $this->archiveFile->getCacheTags();
  }

  /**
   * Get the path alias set on the parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity on which the field is set.
   *
   * @return string
   *   Alias as a string or empty string if there is no alias.
   */
  protected static function getEntityPathAlias(EntityInterface $entity) {
    // @todo: Add support for non-root entities (like paragraphs) to retrieve
    // alias information from.
    if ($entity->hasField('path')) {
      $path_items = $entity->path->get(0)->getValue();
    }

    // Using toUrl() to get aliased entity URL.
    return !empty($path_items) && !empty($path_items['alias']) ? $entity->toUrl()->toString() : '';
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
