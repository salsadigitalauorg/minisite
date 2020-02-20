<?php

namespace Drupal\minisite;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Database;
use Drupal\minisite\Exception\AssetException;
use Drupal\minisite\Exception\InvalidExtensionValidatorException;
use Drupal\minisite\Exception\PageProcessorException;

/**
 * Class Asset.
 *
 * A single Minisite asset.
 *
 * Can represent both documents tracked in the database and non-document files
 * tracked in the repository.
 *
 * @package Drupal\minisite
 */
class Asset implements AssetInterface {

  /**
   * The ID of this asset.
   *
   * Set only if loaded from the database.
   *
   * @var int
   */
  protected $id;

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
   * Asset MIME type.
   *
   * @var string
   *   The MIME type.
   */
  protected $mimeType;

  /**
   * Asset file size.
   *
   * @var int
   *   File size in bytes.
   */
  protected $size;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * A bag of all possible URLs data relevant to this asset.
   *
   * @var \Drupal\minisite\UrlBag
   */
  protected $urlBag;

  /**
   * Asset constructor.
   *
   * @param string $entity_type
   *   The parent entity type.
   * @param string $entity_bundle
   *   The parent entity bundle.
   * @param int $entity_id
   *   The parent entity id.
   * @param string $entity_language
   *   The parent entity language.
   * @param string $field_name
   *   The field name.
   * @param string $file_uri
   *   The URI of the file that this asset represents.
   */
  public function __construct(
    $entity_type,
    $entity_bundle,
    $entity_id,
    $entity_language,
    $field_name,
    $file_uri) {

    $this->entityType = $entity_type;
    $this->entityBundle = $entity_bundle;
    $this->entityId = $entity_id;
    $this->entityLanguage = $entity_language;
    $this->fieldName = $field_name;
    $this->initMimeType($file_uri);
    $this->initSize($file_uri);
    // Create a bag of all URLs relevant to this asset.
    $this->urlBag = new UrlBag($file_uri);
  }

  /**
   * {@inheritdoc}
   */
  public static function fromValues(array $values) {
    $required_fields = [
      'entity_type',
      'entity_bundle',
      'entity_id',
      'entity_language',
      'field_name',
      'source',
    ];

    if (count(array_diff_key(array_flip($required_fields), array_filter($values))) > 0) {
      throw new AssetException('Unable to instantiate Asset instance from the provided values as required values are missing');
    }

    $instance = new self(
      $values['entity_type'],
      $values['entity_bundle'],
      $values['entity_id'],
      $values['entity_language'],
      $values['field_name'],
      $values['source']
    );

    if (!empty($values['id'])) {
      $instance->setId($values['id']);
    }

    if (!empty($values['alias'])) {
      $instance->setAlias($values['alias']);
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id) {
    $values = Database::getConnection()->select('minisite_asset', 'ma')
      ->fields('ma')
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();

    if (empty($values)) {
      return NULL;
    }

    return self::fromValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByUri($uri) {
    if (!LegacyWrapper::isValidUri($uri)) {
      return NULL;
    }

    $values = Database::getConnection()->select('minisite_asset', 'ma')
      ->fields('ma')
      ->condition('source', $uri)
      ->execute()
      ->fetchAssoc();

    if (empty($values)) {
      return NULL;
    }

    return self::fromValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByAlias($alias) {
    if (!UrlHelper::isValid($alias)) {
      return NULL;
    }

    $values = Database::getConnection()->select('minisite_asset', 'ma')
      ->fields('ma')
      ->condition('alias', $alias)
      ->execute()
      ->fetchAssoc();

    if (empty($values)) {
      return NULL;
    }

    return self::fromValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadAll() {
    $values = Database::getConnection()->select('minisite_asset', 'ma')
      ->fields('ma')
      ->execute()
      ->fetchAllAssoc('id', \PDO::FETCH_ASSOC);

    $assets = [];

    foreach ($values as $value) {
      $assets[] = self::fromValues($value);
    }

    return $assets;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $fields = [
      'entity_type' => $this->entityType,
      'entity_bundle' => $this->entityBundle,
      'entity_id' => $this->entityId,
      'entity_language' => $this->entityLanguage,
      'field_name' => $this->fieldName,
      'source' => $this->getUri(),
      'alias' => !empty($this->getAlias()) ? $this->getAlias() : '',
      'filemime' => $this->getMimeType(),
      'filesize' => $this->getSize(),
    ];

    if (!empty($this->id)) {
      $fields['id'] = $this->id;
    }

    if (empty($fields['id'])) {
      $id = Database::getConnection()->insert('minisite_asset')
        ->fields($fields)
        ->execute();
    }
    else {
      $id = Database::getConnection()->update('minisite_asset')
        ->fields($fields)
        ->condition('id', $fields['id'])
        ->execute();
    }
    $this->setId($id);

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Only delete assets that are stored in the database.
    if (isset($this->id)) {
      Database::getConnection()->delete('minisite_asset')
        ->condition('id', $this->id())
        ->execute();
      $this->id = NULL;
    }

    /** @var \Drupal\Core\File\FileSystem $fs */
    $fs = \Drupal::service('file_system');

    $fs->deleteRecursive($this->urlBag->getUri());

    // Remove parent directories if there is no other files up until common
    // assets directory.
    $dirname = $this->urlBag->getUri();
    while (($dirname = $fs->dirname($dirname)) && $dirname != Minisite::getCommonAssetDir()) {
      if (empty(LegacyWrapper::scanDirectory($dirname, '/.*/'))) {
        $fs->deleteRecursive($dirname);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $file_uri = $this->urlBag->getUri();

    // Last check that asset file exist before render.
    if (!is_readable($file_uri)) {
      throw new AssetException(sprintf('Unable to render file "%s" as it does not exist', $file_uri));
    }

    $content = file_get_contents($file_uri);

    // Only process documents.
    if ($this->isDocument()) {
      try {
        $processor = new PageProcessor($content, $this->urlBag);
        $processor->process();
        $content = $processor->content();
      }
      catch (PageProcessorException $exception) {
        // Simply pass-through as unprocessed content on processor exception and
        // fail for anything else.
      }
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return isset($this->id) ? $this->id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Get asset URI.
   *
   * @return string
   *   The asset URI within the file system.
   */
  public function getUri() {
    return $this->urlBag->getUri();
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    $alias = $this->getAlias();

    return !empty($alias) ? $alias : $this->urlBag->getUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getAlias() {
    return $this->urlBag->getAlias();
  }

  /**
   * {@inheritdoc}
   */
  public function setAlias($alias) {
    $this->urlBag->setAlias($alias);
  }

  /**
   * {@inheritdoc}
   */
  public function setAliasPrefix($parent_alias) {
    $this->urlBag->setParentAlias($parent_alias);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    return $this->entityLanguage;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->mimeType;
  }

  /**
   * {@inheritdoc}
   */
  public function getSize() {
    return $this->size;
  }

  /**
   * {@inheritdoc}
   */
  public function isIndex() {
    if (!UrlValidator::urlIsIndex($this->urlBag->getUri(), self::INDEX_FILE)) {
      return FALSE;
    }

    $path = $this->urlBag->getPathInArchive();
    $in_root = strpos($path, DIRECTORY_SEPARATOR) == FALSE;

    return $in_root;
  }

  /**
   * Array of headers for current asset.
   *
   * @return array
   *   Array of headers keyed by header name.
   */
  public function getHeaders() {
    $headers = [];

    if ($this->isDocument()) {
      $headers['Content-Language'] = $this->getLanguage();
      $headers['Content-Type'] = $this->getMimeType() . '; charset=UTF-8';
    }
    else {
      $type = Unicode::mimeHeaderEncode($this->getMimeType());
      $headers['Content-Type'] = $type;
      $headers['Content-Length'] = $this->getSize();
      $headers['Cache-Control'] = 'private';
    }

    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function isDocument() {
    try {
      FileValidator::validateFileExtension($this->urlBag->getUri(), ['html', 'htm']);

      return TRUE;
    }
    catch (InvalidExtensionValidatorException $exception) {
      // Do nothing as this is expected.
    }

    return FALSE;
  }

  /**
   * Initialise mime type based on file type.
   */
  public function initMimeType($uri) {
    $this->mimeType = \Drupal::service('file.mime_type.guesser')->guess($uri);
  }

  /**
   * Initialise file size.
   */
  public function initSize($uri) {
    $this->size = @filesize($uri);
  }

}
