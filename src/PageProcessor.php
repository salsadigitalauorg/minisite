<?php

namespace Drupal\minisite;

use Drupal\Component\Utility\UrlHelper;
use Drupal\minisite\Exception\PageProcessorException;

/**
 * Class PageProcessor.
 *
 * Process pages to replace links etc.
 *
 * @package Drupal\minisite
 */
class PageProcessor implements PageProcessorInterface {

  /**
   * The DOM document loaded for processing.
   *
   * @var \DOMDocument
   */
  protected $document;

  /**
   * A container of URLs used for link replacements while processing document.
   *
   * @var \Drupal\minisite\UrlBag
   */
  protected $urlBag;

  /**
   * PageProcessor constructor.
   *
   * @param string $content
   *   Content to process.
   * @param \Drupal\minisite\UrlBag $url_bag
   *   A container for all URL used for the replacement of links. Usually,
   *   based on current path where document is accessed from.
   *
   * @throws \Drupal\minisite\Exception\PageProcessorException
   *   If the provided content cannot be parsed into \DOMDocument.
   */
  public function __construct($content, UrlBag $url_bag) {
    $this->urlBag = $url_bag;
    $this->document = $this->loadDocument($content);
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    $this->processTagBase();

    foreach ($this->document->getElementsByTagName('a') as $item) {
      $this->processTagA($item);
    }

    foreach ($this->document->getElementsByTagName('link') as $item) {
      $this->processTagLink($item);
    }

    foreach ($this->document->getElementsByTagName('img') as $item) {
      $this->processTagImg($item);
    }

    foreach ($this->document->getElementsByTagName('script') as $item) {
      $this->processTagScript($item);
    }

    foreach ($this->document->getElementsByTagName('style') as $item) {
      $this->processTagStyle($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function urlIsDocumentFile($url) {
    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote(PageProcessorInterface::EXTENSIONS_NON_HTML_DOCUMENTS)) . ')$/i';

    return (bool) preg_match($regex, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function content() {
    return $this->document->saveHTML();
  }

  /**
   * Process <base> tag.
   *
   * @throws \Drupal\minisite\Exception\PageProcessorException
   *   If 'head' element is missing.
   */
  protected function processTagBase() {
    $base_tag = $this->document->getElementsByTagName('base')->item(0);

    if ($base_tag) {
      // Remove the tag as all paths are resolved against the root of the parent
      // alias.
      $this->document->removeChild($base_tag);
    }
  }

  /**
   * Process <a> tag.
   *
   * @param \DOMNode $item
   *   Document node object to process.
   */
  protected function processTagA(\DOMNode $item) {
    $url = $item->getAttribute('href');

    if (!$url) {
      return;
    }

    // Skip absolute URLs, because we cannot guarantee the correctness of any
    // replacements.
    if (UrlValidator::urlIsExternal($url)) {
      return;
    }

    // If href points to a root of the file structure - we are most likely in
    // the document linking to the root of the archive, so we have to replace
    // it with a relative path to the root (based on the current document
    // depth).
    // Note, that this is a case when we are "fixing" links that should not be
    // linking to root, so this is still a "best effort" approach.
    if (UrlValidator::urlIsRoot($url)) {
      $item->setAttribute('href', UrlValidator::rootToRelative($url, $this->urlBag->getRootDir(), $this->urlBag->getParentAlias()));

      return;
    }

    // If href is a relative path - skip processing as it is not possible to
    // "guess" the correct files in the tree (i.e., could be pointing to the
    // file with the same name from another dir etc., which is even more
    // confusing).
    if (UrlValidator::urlIsRelative($url) && self::urlIsDocumentFile($url)) {
      $url = self::urlExtractPath($url);
      $url = UrlValidator::relativeToRoot($url, $this->urlBag->getAssetDir() . '/' . $this->urlBag->getRootDir());
      $item->setAttribute('href', $url);

      return;
    }
  }

  /**
   * Process <link> tag.
   *
   * @param \DOMNode $item
   *   Document node object to process.
   */
  protected function processTagLink(\DOMNode $item) {
    $url = $item->getAttribute('href');

    if (!$url) {
      return;
    }

    // Skip absolute URLs, because we cannot guarantee the correctness of any
    // replacements.
    if (UrlValidator::urlIsExternal($url)) {
      return;
    }

    // If href points to a root of the file structure - we are most likely in
    // the document linking to the root of the archive, so we have to replace
    // it with a relative path to the root (based on the current document
    // depth).
    // Note, that this is a case when we are "fixing" links that should not be
    // linking to root, so this is still a "best effort" approach.
    if (UrlValidator::urlIsRoot($url)) {
      $item->setAttribute('href', UrlValidator::rootToRelative($url, $this->urlBag->getRootDir(), $this->urlBag->getParentAlias()));

      return;
    }

    $url = self::urlExtractPath($url);
    $url = UrlValidator::relativeToRoot($url, $this->urlBag->getAssetDir() . '/' . $this->urlBag->getRootDir());
    $item->setAttribute('href', $url);
  }

  /**
   * Process <script> tag.
   *
   * @param \DOMNode $item
   *   Document node object to process.
   */
  protected function processTagScript(\DOMNode $item) {
    $url = $item->getAttribute('src');

    if (!$url) {
      return;
    }

    $url = self::urlExtractPath($url);
    $url = UrlValidator::relativeToRoot($url, $this->urlBag->getAssetDir() . '/' . $this->urlBag->getRootDir());
    $item->setAttribute('src', $url);
  }

  /**
   * Process <style> tag.
   *
   * @param \DOMNode $item
   *   Document node object to process.
   */
  protected function processTagStyle(\DOMNode $item) {
    $content = $item->textContent;

    // Replace imported styles.
    preg_match_all('/@import url\(([^)]+)\)/i', $content, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
      return;
    }

    foreach ($matches as $match) {
      if (count($match) != 2) {
        continue;
      }

      $url = $match[1];

      $url = self::urlExtractPath($url);
      $url = UrlValidator::relativeToRoot($url, $this->urlBag->getAssetDir() . '/' . $this->urlBag->getRootDir());

      $str = str_replace($match[1], $url, $match[0]);
      $content = str_replace($match[0], $str, $content);
    }

    $item->textContent = $content;
  }

  /**
   * Process <img> tag.
   *
   * @param \DOMNode $item
   *   Document node object to process.
   */
  protected function processTagImg(\DOMNode $item) {
    $url = $item->getAttribute('src');

    if (!$url) {
      return;
    }

    $item->setAttribute('src', UrlValidator::relativeToRoot($url, $this->urlBag->getAssetDir() . '/' . $this->urlBag->getRootDir()));
  }

  /**
   * Load document from the string.
   *
   * @param string $content
   *   The content to load. Should be an HTML page that can be parsed.
   *
   * @return \DOMDocument
   *   DOM Document object with loaded content.
   *
   * @throws \Drupal\minisite\Exception\PageProcessorException
   *   If provided content is not valid HTML or empty.
   */
  protected function loadDocument($content) {
    libxml_use_internal_errors(TRUE);

    $document = new \DOMDocument();

    $content = $this->cleanupContent($content);

    $loaded = $document->loadHTML($content);
    if (!$loaded || empty($document) || empty($document->textContent)) {
      throw new PageProcessorException(sprintf('Unable to parse document: %s', libxml_get_last_error()));
    }

    return $document;
  }

  /**
   * Cleanup content of the page before loading it int internal document.
   */
  protected function cleanupContent($content) {
    $content = preg_replace('/\<meta\s+http-equiv\s*=\s*\"content-type\"\s+content\s*=\s*\".*charset=ISO-8859-1\"\s*(\/?)\>/i', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $content);
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");

    return $content;
  }

  /**
   * Extract path from the URL.
   *
   * @param string $url
   *   URL to assess.
   *
   * @return string|null
   *   Path if exists in the URL, NULL otherwise.
   */
  protected static function urlExtractPath($url) {
    $parsed = UrlHelper::parse($url);

    return isset($parsed['path']) ? $parsed['path'] : NULL;
  }

}
