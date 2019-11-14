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
    $this->fixtureDir = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . rand(100000, 1000000);
    $fs->mkdir($this->fixtureDir);
  }

  protected function fixtureTearDown() {
    $fs = new Filesystem();
    if ($fs->exists($this->fixtureDir)) {
      $fs->remove($this->fixtureDir);
    }
    $this->fixtureDir = NULL;
  }

  public function createFixtureArchive($files, $filename = NULL) {

  }

  public function createFixtureFiles($files, $filename = NULL) {
    $dirs = [];
    foreach ($files as $name => $value) {
      // Directories are entries with int key.
      if (is_int($name)) {
        $dirs[] = $this->fixtureDir . \DIRECTORY_SEPARATOR . $value;
      }
      unset($files[$name]);
    }

    $fs = new Filesystem();
    $fs->mkdir($dirs);

    foreach ($files as $name => $content) {
      $fs->dumpFile($this->fixtureDir . \DIRECTORY_SEPARATOR . $name, $content);
    }
  }

}
