<?php

namespace Drupal\Tests\minisite\Traits;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @file
 *
 */
trait FixtureTrait {

  protected $fixtureDir;

  protected function fixtureSetUp() {
    $fs = new Filesystem();
    $this->fixtureDir = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . uniqid();
    $fs->mkdir($this->fixtureDir);
  }

  protected function fixtureTearDown() {
    $fs = new Filesystem();
    if ($fs->exists($this->fixtureDir)) {
      $fs->remove($this->fixtureDir);
    }
    $this->fixtureDir = NULL;
  }

  public function fixtureCreateFiles($files) {
    $paths = [];

    $dirs = [];
    foreach ($files as $name => $value) {
      // Directories are entries with int key.
      if (is_int($name)) {
        $path = $this->fixtureDir . \DIRECTORY_SEPARATOR . $value;
        $dirs[] = $path;
        $paths[$path] = $value;
        unset($files[$name]);
      }
    }

    $fs = new Filesystem();
    $fs->mkdir($dirs);

    foreach ($files as $name => $content) {
      $path = $this->fixtureDir . \DIRECTORY_SEPARATOR . $name;
      $fs->dumpFile($path, $content);
      $paths[$path] = $name;
    }

    return $paths;
  }

  public function fixtureCreateArchive($files, $type = 'zip', $filename = NULL) {
    $filename = empty($filename) ? uniqid() : $filename;
    $filename = basename($filename, '.' . $type) . '.' . $type;
    $file_path = $this->fixtureDir . \DIRECTORY_SEPARATOR . uniqid() . \DIRECTORY_SEPARATOR . $filename;

    $fs = new Filesystem();
    $fs->mkdir(dirname($file_path));

    switch ($type) {
      case 'zip':
        $archive = new \ZipArchive();
        if ($archive->open($file_path, \ZipArchive::CREATE) !== TRUE) {
          throw new \RuntimeException(sprintf('Cannot open file "%s"', $file_path));
        }
        break;

      default:
        throw new \RuntimeException(sprintf('Unsupported archive type "%s" provided.', $type));
    }

    $files = $this->fixtureCreateFiles($files);
    foreach ($files as $absolute_path => $path) {
      if (is_dir($absolute_path)) {
        $archive->addEmptyDir($path);
      }
      else {
        $archive->addFile($absolute_path, $path);
      }
    }
    $archive->close();

    return $file_path;
  }

}
