<?php

namespace Drupal\minisite;

/**
 * Interface PageProcessorInterface.
 *
 * @package Drupal\minisite
 */
interface PageProcessorInterface {

  /**
   * Extensions to be considered as non-HTML documents.
   *
   * Links for such documents may be rewritten in a different way than other
   * files.
   */
  const EXTENSIONS_NON_HTML_DOCUMENTS = 'pdf doc docx ppt pptx xls xlsx tif xml txt';

  /**
   * Process document.
   */
  public function process();

  /**
   * Get content of the document being processed.
   *
   * @return string
   *   The document content as HTML.
   */
  public function content();

  /**
   * Check if the provxided URL is a non-HTML document.
   *
   * @param string $url
   *   URL to assess.
   *
   * @return string
   *   TRUE if the provided URL is a non-HTML document, FALSE otherwise.
   */
  public static function urlIsDocumentFile($url);

}
