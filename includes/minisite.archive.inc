<?php

/**
 * @file
 * Minisite archive class.
 */

/**
 * Class MinisiteArchive.
 */
class MinisiteArchive {

  protected $archiver;

  /**
   * Open archive file.
   */
  public static function open(\stdClass $file) {
    $archive = new static();
    $file_path = drupal_realpath($file->uri);
    try {
      switch ($file->filemime) {
        case 'application/zip':
          $archive->archiver = new \ArchiverZip($file_path);
          $archive->archiver->listContents();
          break;

        default:
          return FALSE;
      }
    } catch (\Exception $e) {
      return FALSE;
    }

    return $archive;
  }

  /**
   * Exact files to given path.
   */
  public function extract($path, Array $files = array()) {
    if ($files) {
      $this->archiver->extract($path, $files);
    }
    else {
      $this->archiver->extract($path);
    }

    return $this;
  }

  /**
   * Remove file from archive file.
   */
  public function fileRemove($file_path) {
    $this->archiver->remove($file_path);

    return $this;
  }

  /**
   * Return files listing.
   */
  public function filesList() {
    $listing = $this->archiver->listContents();
    foreach ($listing as $k => $file) {
      if (strpos(strtolower($file), '.ds_store') !== FALSE) {
        $this->fileRemove($file);
        unset($listing[$k]);
      }
      if (strpos(strtolower($file), '__macosx') !== FALSE) {
        unset($listing[$k]);
        $this->fileRemove($file);
      }
      if (strpos(strtolower($file), '_notes') !== FALSE) {
        $this->fileRemove($file);
        unset($listing[$k]);
      }
      if (strpos(strtolower($file), '.dropbox') !== FALSE) {
        $this->fileRemove($file);
        unset($listing[$k]);
      }
      if (strpos(strtolower($file), 'thumbs.db') !== FALSE) {
        $this->fileRemove($file);
        unset($listing[$k]);
      }
      if (strpos(strtolower($file), 'desktop.ini') !== FALSE) {
        $this->fileRemove($file);
        unset($listing[$k]);
      }
      if (!$file) {
        unset($listing[$k]);
      }
    }

    return $listing;
  }

  /**
   * Return files tree listing.
   */
  public function filesTree() {
    $listing = $this->filesList();
    $tree = array();
    foreach ($listing as $file_path) {
      $parts = explode('/', $file_path);
      // Files in archive end in / if a directory.
      if (substr($file_path, -1) === '/') {
        $parts = array_slice($parts, 0, -1);
        drupal_array_set_nested_value($tree, $parts, array('.' => $file_path));
      }
      else {
        drupal_array_set_nested_value($tree, $parts, $file_path);
      }
    }

    return $tree;
  }

  /**
   * Checks that the filename ends with an allowed extension.
   */
  public static function fileExtensionCheck($files, $extensions) {
    $invalid_files = array();

    foreach ($files as $file_name) {
      // Ignore folder in minisite.
      if (substr($file_name, -1) == '/') {
        continue;
      }
      $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($extensions)) . ')$/i';
      if (!preg_match($regex, $file_name)) {
        $invalid_files[] = $file_name;
      }
    }

    return $invalid_files;
  }

}
