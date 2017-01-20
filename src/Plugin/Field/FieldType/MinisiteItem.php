<?php

namespace Drupal\minisite\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\minisite\Minisite;

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
  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = array(
        'file_extensions' => 'zip',
        'file_directory' => MINISITE_UPLOAD_PATH,
        'minisite_extensions' => MINISITE_EXTENSIONS_WHITELIST,
        'minisite_extensions_disallowed' => MINISITE_EXTENSIONS_BLACKLIST,
      ) + parent::defaultFieldSettings();

    unset($settings['description_field']);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'target_id' => array(
          'description' => 'The ID of the file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ),
        'asset_path' => array(
          'description' => 'The URI of the minisite asset path.',
          'type' => 'varchar',
          'length' => 255,
        ),
        'options' => array(
          'description' => 'Serialized array of options for the link.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ),
      ),
      'indexes' => array(
        'target_id' => array('target_id'),
        'asset_path' => array('asset_path'),
      ),
      'foreign keys' => array(
        'target_id' => array(
          'table' => 'file_managed',
          'columns' => array('target_id' => 'fid'),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    unset($properties['display']);
    unset($properties['description']);

    $properties['asset_path'] = DataDefinition::create('string')
      ->setLabel(t('Minisite asset path'));

    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Options'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();

    // We need the field-level 'minisite' setting, and $this->getSettings()
    // will only provide the instance-level one, so we need to explicitly fetch
    // the field.
    $settings = $this->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getSettings();

    $scheme_options = \Drupal::service('stream_wrapper_manager')
      ->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $element['uri_scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $settings['uri_scheme'],
      '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
    );

    // Add more storage settings.

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
    $element['minisite_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions in uploaded minisite files'),
      '#default_value' => $extensions,
      '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => array(
        array(
          get_class($this),
          'validateExtensions',
        ),
      ),
      '#weight' => 11,
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    // Minisite presave.
    $this->asset_path = Minisite::preSave($this->entity, $this->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayed() {
    // Minisite items do not have per-item visibility settings.
    return TRUE;
  }
}
