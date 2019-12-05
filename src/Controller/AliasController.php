<?php

namespace Drupal\minisite\Controller;

use Drupal\minisite\Asset;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AliasController.
 *
 * Controller to deliver a single aliased asset.
 * Non-aliased assets and non-html documents are never delivered through this
 * controller.
 *
 * @package Drupal\minisite\Controller
 */
class AliasController implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Request callback to deliver a single minisite asset.
   *
   * @param int $asset_id
   *   Minisite asset id.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function deliverAsset($asset_id) {
    $asset = Asset::load($asset_id);

    // Only deliver documents through alias controller. There are other checks
    // in the class itself, but this is one last gate keeping check to
    // explicitly prevent non-documents from being served through alias
    // callback.
    if (!$asset || !$asset->isDocument()) {
      throw new NotFoundHttpException();
    }

    try {
      $render = $asset->render();
      $response = new Response($render);
    }
    catch (\Exception $exception) {
      throw new NotFoundHttpException();
    }

    $this->addResponseHeaders($response, $asset);

    return $response;
  }

  /**
   * Add headers to the response object.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response object.
   * @param \Drupal\minisite\Asset $asset
   *   The loaded asset to be used for contextual data.
   */
  protected function addResponseHeaders(Response $response, Asset $asset) {
    $headers = [];

    $headers['Content-Language'] = $asset->getLanguage();
    if (!$response->headers->has('Content-Type')) {
      $headers['Content-Type'] = 'text/html; charset=utf-8';
    }

    $response->headers->add($headers);
  }

}
