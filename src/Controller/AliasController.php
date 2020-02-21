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

    if (!$asset) {
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
    // @todo: Review and implement better caching strategy + add tests.
    $response->setPublic();

    $max_age = $asset->getCacheMaxAge();
    $response->setMaxAge($max_age);

    $expires = new \DateTime();
    $expires->setTimestamp(\Drupal::time()->getRequestTime() + $max_age);
    $response->setExpires($expires);

    $response->headers->add($asset->getHeaders());
  }

}
