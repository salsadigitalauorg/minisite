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
use Drupal\minisite\Exception\MissingArchiveException;

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
   */
  protected $assetContainer;

  /**
   * Allowed extensions of files in archive as a space-separated string.
   *
   * @var string
   */
  protected $allowedExtensions;

  /**
   * Minisite constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Parent entity where the minisite field is located.
   * @param string $field_name
   *   The minisite field name.
   * @param \Drupal\file\FileInterface $archive_file
   *   The archive managed file.
   * @param string $allowed_extensions
   *   Allowed extensions of files in archive as a space-separated string.
   */
  public function __construct(EntityInterface $entity, $field_name, FileInterface $archive_file, $allowed_extensions = MinisiteInterface::ALLOWED_EXTENSIONS) {
    $this->entityType = $entity->getEntityTypeId();
    $this->entityBundle = $entity->bundle();
    $this->entityId = $entity->id();
    $this->entityLanguage = $entity->language()->getId();
    $this->fieldName = $field_name;
    $this->allowedExtensions = $allowed_extensions;
    $this->setArchiveFile($archive_file);
    $this->description = '';
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
      $entity = $items->getEntity();
      $description = '';
      $parent_alias = '';

      if (!empty($items->get(0))) {
        $description = $items->get(0)->description;

        // Set alias for all assets of this minisite if it was selected for this
        // field on this entity and the entity has a path alias set.
        $value = $items->get(0)->getValue();
        $entity_alias = self::getEntityPathAlias($entity);
        if (!empty($value['options']['alias_status']) && !empty($entity_alias)) {
          $parent_alias = $entity_alias;
        }
      }

      $instance = new self($entity, $field_definition->getName(), $archive_file, $field_definition->getSetting('minisite_extensions'));
      if (!empty($description)) {
        $instance->setDescription($description);
      }

      if (!empty($parent_alias)) {
        $instance->setAlias($parent_alias);
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
    static::validateArchive($file, $this->allowedExtensions);

    $this->archiveFile = $file;

    // Always process archive when file changes. This does not
    // necessarily mean extracting the archive into new directory every time
    // this method is called but will guarantee that the archive have
    // been extracted and all files are ready.
    $this->processArchive();
  }

  /**
   * {@inheritdoc}
   */
  public function setAlias($alias_prefix) {
    $this->aliasPrefix = $alias_prefix;

    if (isset($this->assetContainer)) {
      $this->assetContainer->updateAliases($alias_prefix);
    }
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
   * Process archive by extracting files and filling-in assets information.
   */
  protected function processArchive() {
    $asset_directory = $this->prepareAssetDirectory();

    // Scan directory for previously extracted files.
    // Note that we are not checking if _all_ files from archive exist: if any
    // were removed - the archive file would need to be re-uploaded to have
    // the files re-extracted.
    $files = LegacyWrapper::scanDirectory($asset_directory, '/.*/');

    if (!$files) {
      // Files do not exist - looks like this is a first time processing, so
      // we need to extract files. At this point, the archive file has already
      // been validated, so it is a matter of extracting files.
      $archiver = self::getArchiver($this->archiveFile->getFileUri());
      $archiver->listContents();
      $archiver->extract($asset_directory);
      // Re-scan files directory.
      $files = LegacyWrapper::scanDirectory($asset_directory, '/.*/');
    }

    $this->assetContainer = new AssetContainer();

    foreach (array_keys($files) as $file_uri) {
      // Refactor to pass an entity instead of it's fields.
      $this->assetContainer->add(
        $this->entityType,
        $this->entityBundle,
        $this->entityId,
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
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
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
    // Does physical file exist?
    if (!is_readable($file->getFileUri())) {
      throw new MissingArchiveException($file->getFileUri());
    }

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
    /** @var \Drupal\Core\File\FileSystemInterface $fs */
    $fs = \Drupal::service('file_system');

    $dir = $this->getAssetDirectory();
    if (!file_exists($dir)) {
      if (!$fs->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        throw new AssetException(sprintf('Unable to prepare asset directory "%s"', $dir));
      }
    }

    return $dir;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssetDirectory() {
    $suffix = $this->archiveFile->get('uuid')->value;

    return self::getCommonAssetDir() . DIRECTORY_SEPARATOR . $suffix;
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
    $fs = \Drupal::service('file_system');
    $filename_real = $fs->realpath($uri);
    $filename = $filename ? $filename : $fs->basename($uri);

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
