<?php

namespace Drupal\minisite\Controller;

use Drupal\minisite\Asset;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class MinisiteController.
 *
 * @package Drupal\minisite\Controller
 */
class MinisiteController implements ContainerAwareInterface {

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
  public function deliverMinisiteAsset($asset_id) {
    $asset = Asset::load($asset_id);

    if (!$asset || !$asset->isDocument()) {
      throw new NotFoundHttpException();
    }

    try {
      $response = new Response($asset->render());
    }
    catch (\Exception $exception) {
      throw new NotFoundHttpException();
    }

    $headers = [];
    $headers['Content-Language'] = $asset->getLanguage();
    if (!$response->headers->has('Content-Type')) {
      $headers['Content-Type'] = 'text/html; charset=utf-8';
    }
    $response->headers->add($headers);

    return $response;
  }

}
