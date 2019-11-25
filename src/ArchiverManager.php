<?php

namespace Drupal\minisite;

use Drupal\Core\Archiver\ArchiverManager as CoreArchiverManager;

/**
 * Class ArchiverManager.
 *
 * @package Drupal\minisite
 */
class ArchiverManager extends CoreArchiverManager {

  /**
   * {@inheritdoc}
   *
   * Override core's archiver to accept file name as option and use it instead
   * of filepath to discover archiver class. File path is still used to
   * instantiate archiver class. This is required for cases where file path and
   * file name are different (e.g., during file upload process).
   */
  public function getInstance(array $options) {
    $filename = isset($options['filename']) ? $options['filename'] : NULL;

    if (!$filename) {
      // Fallback to the parent implementation if file name was not provided.
      return parent::getInstance($options);
    }

    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      foreach ($definition['extensions'] as $extension) {
        // Because extensions may be multi-part, such as .tar.gz,
        // we cannot use simpler approaches like substr() or pathinfo().
        // This method isn't quite as clean but gets the job done.
        // Also note that the file may not yet exist, so we cannot rely
        // on fileinfo() or other disk-level utilities.
        if (strrpos($filename, '.' . $extension) === strlen($filename) - strlen('.' . $extension)) {
          return $this->createInstance($plugin_id, $options);
        }
      }
    }
  }

}
