<?php

namespace Drupal\Tests\minisite\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\minisite\PageProcessor;
use Drupal\minisite\UrlBag;
use Drupal\Tests\minisite\Traits\MockHelperTrait;

/**
 * Class PageProcessorTest.
 *
 * Tests document processor.
 *
 * @group minisite
 *
 * @package Drupal\testmode\Tests
 */
class PageProcessorTest extends KernelTestBase {

  use MockHelperTrait;

  /**
   * Test process() method.
   *
   * @dataProvider dataProviderProcess
   * @covers \Drupal\minisite\PageProcessor::process
   */
  public function testProcess($base_url, $uri, $alias, $href, $expected) {
    $content = '<!doctype html><html lang="en"><head></head><body><a href="' . $href . '">Test link</a></body></html>';
    $bag = new UrlBag($uri, $base_url);
    $bag->setParentAlias($alias);
    $processor = new PageProcessor($content, $bag);
    $processor->process();
    $actual = $this->getTagAttribute($processor->content(), 'a', 'href');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testProcessTagA().
   */
  public function dataProviderProcess() {
    // @todo: Add more tests.
    return [
      [
        'http://example.com',
        'public://minisite/static/24c22dd1-2cf1-47ae-ac8a-23a7ff8b86c5/rootpath/subpath/page1.html',
        'sub/path/alias',
        '/page2.html',
        '/sub/path/alias/rootpath/page2.html',
      ],
    ];
  }

  /**
   * Helper to get an attribute from the tag.
   */
  protected function getTagAttribute($content, $tag, $attribute, $index = 0) {
    libxml_use_internal_errors(TRUE);
    $document = new \DOMDocument();

    $document->loadHTML($content);

    $count = 0;
    foreach ($document->getElementsByTagName($tag) as $item) {
      if ($index == $count) {
        return $item->getAttribute($attribute);
      }
      $count++;
    }

    return NULL;
  }

}
