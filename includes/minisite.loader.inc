<?php

/**
 * @file
 * Minisite lib loader.
 */

/**
 * Class MinisiteAutoloaderInit.
 */
class MinisiteAutoloaderInit {
  private static $loader;

  /**
   * @param $class
   */
  public static function loadClassLoader($class) {
    if ('Minisite\Autoload\ClassLoader' === $class) {
      require __DIR__ . '/../lib/minisite-php/src/ClassLoader.php';
    }
  }


  public static function getLoader() {
    if (NULL !== self::$loader) {
      return self::$loader;
    }

    spl_autoload_register(array(
      'MinisiteAutoloaderInit',
      'loadClassLoader',
    ), TRUE, TRUE);
    self::$loader = $loader = new \Minisite\Autoload\ClassLoader();
    spl_autoload_unregister(array('MinisiteAutoloaderInit', 'loadClassLoader'));

    $loader->set('Minisite', __DIR__ . '/../lib/minisite-php/src');
    $loader->setPsr4('Minisite\\', __DIR__ . '/../lib/minisite-php/src');
    $loader->register(TRUE);
    $loader->setUseIncludePath(TRUE);

    // Load functions.
    require __DIR__ . '/../lib/minisite-php/src/functions_include.php';

    return $loader;
  }
}

return MinisiteAutoloaderInit::getLoader();
