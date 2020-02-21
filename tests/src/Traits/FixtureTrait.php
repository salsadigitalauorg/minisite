<?php

namespace Drupal\Tests\minisite\Traits;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Trait FixtureTrait.
 *
 * @package Drupal\Tests\minisite\Traits
 */
trait FixtureTrait {

  /**
   * The directory where fixture files reside for this test.
   *
   * @var string
   */
  protected $fixtureDir;

  /**
   * Set up functionality.
   *
   * Must be added to setUp() method of the test class.
   */
  protected function fixtureSetUp() {
    $fs = new Filesystem();
    $this->fixtureDir = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . uniqid();
    $fs->mkdir($this->fixtureDir);
  }

  /**
   * Tear down functionality.
   *
   * Must be added to tearDown() method of the test class.
   */
  protected function fixtureTearDown() {
    $fs = new Filesystem();
    if ($fs->exists($this->fixtureDir)) {
      $fs->remove($this->fixtureDir);
    }
    $this->fixtureDir = NULL;
  }

  /**
   * Create directories and files with content.
   *
   * @param array $files
   *   Array of files. If the key is integer - the value considered to be a
   *   directory; if the key is string, the key is considered to be a file name
   *   and the value is a content of the file.
   *   For files within directories, all parent directories are created.
   *
   * @return array
   *   Array of created files, keyed by absolute path to the created files.
   */
  public function fixtureCreateFiles(array $files) {
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

  /**
   * Create a single file with content.
   *
   * @param string $filename
   *   File name of the resulting file.
   * @param string $content
   *   (optional) Content of the resulting file.
   *
   * @return string
   *   Absolute path to created file.
   */
  public function fixtureCreateFile($filename, $content = '') {
    $created_files = $this->fixtureCreateFiles([$filename => $content]);
    $created_file = key($created_files);

    return $created_file;
  }

  /**
   * Create archive from an array of specified files.
   *
   * @param array $files
   *   Array of files as described in fixtureCreateFiles().
   * @param string $type
   *   (optional) The type of the archive. Defaults to 'zip'.
   * @param string $filename
   *   (optional) The resulting file name of the archive. If not provided, a
   *   random file name is generated.
   *
   * @return string
   *   Absolute path to created archive file.
   */
  public function fixtureCreateArchive(array $files, $type = 'zip', $filename = NULL) {
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

  /**
   * Create a fixture HTML page.
   *
   * @param ...
   *   Arguments concatenated with '<br/>' and inserted into '<body>'.
   *
   * @return string
   *   HTML5 string with added content.
   */
  public function fixtureHtmlPage() {
    $content = implode('<br/>', func_get_args());
    $html = <<<HEREDOC
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
</head>
<body>
$content
</body>
</html>
HEREDOC;

    return $html;
  }

  /**
   * Create a fixture link.
   *
   * Used to avoid calling Drupal theme system.
   *
   * @param string $text
   *   Link text.
   * @param string $url
   *   Link URL.
   *
   * @return string
   *   Html anchor link.
   */
  public function fixtureLink($text, $url) {
    return "<a href=\"$url\">$text</a>";
  }

  /**
   * Get the path to the directory with fixture files.
   *
   * @return string
   *   The path to the directory with fixture files.
   */
  public function getFixtureFileDir() {
    return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'fixtures';
  }

}
