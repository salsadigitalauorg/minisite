<?php

namespace Drupal\minisite\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\minisite\Minisite;
use Drupal\minisite\MinisiteInterface;

/**
 * Plugin implementation of the 'minisite' field type.
 *
 * @FieldType(
 *   id = "minisite",
 *   label = @Translation("Minisite"),
 *   description = @Translation("Stores the ID, path and optional blob of a minisite file."),
 *   category = @Translation("Reference"),
 *   default_widget = "minisite_default",
 *   default_formatter = "minisite_link",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class MinisiteItem extends FileItem {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $defaults = [
      'file_extensions' => 'zip',
      'file_directory' => MinisiteInterface::ARCHIVE_UPLOAD_DIR,
      'minisite_extensions' => MinisiteInterface::ALLOWED_EXTENSIONS,
    ];
    $settings = $defaults + parent::defaultFieldSettings();

    unset($settings['description_field']);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'asset_path' => [
          'description' => 'The URI of the entry point minisite asset path (index.html).',
          'type' => 'varchar',
          'length' => 255,
        ],
        'options' => [
          'description' => 'Serialized array of options for the link.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
        'asset_path' => ['asset_path'],
      ],
      'foreign keys' => [
        'target_id' => [
          'table' => 'file_managed',
          'columns' => ['target_id' => 'fid'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Remove properties set by the parent class.
    unset($properties['display']);
    unset($properties['description']);

    $properties['asset_path'] = DataDefinition::create('string')->setLabel(t('Minisite asset path'));

    $properties['options'] = MapDataDefinition::create()->setLabel(t('Options'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    // We need the field-level 'minisite' setting and $this->getSettings()
    // to only provide the instance-level one, so we need to explicitly fetch
    // the field.
    $settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();

    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);

    $element['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $settings['uri_scheme'],
      '#description' => $this->t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();

    // Remove the description option.
    unset($element['description_field']);

    // Make the extension list a little more human-friendly by comma-separation.
    $extensions = str_replace(' ', ', ', $settings['minisite_extensions']);

    $element['minisite_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions in uploaded minisite files'),
      '#default_value' => $extensions,
      '#description' => $this->t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => [[get_class($this), 'validateExtensions']],
      '#weight' => 11,
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    // Set asset path from uploaded archive.
    $minisite = Minisite::fromArchive($this->entity, $this->getFieldDefinition()->getSetting('minisite_extensions'));
    if ($minisite) {
      $this->asset_path = $minisite->getIndexAssetUri();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayed() {
    // Override parent class setting as Minisite items do not have per-item
    // visibility settings.
    return TRUE;
  }

}
