<?php

namespace Drupal\minisite\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\minisite\Asset;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AliasSubscriber.
 *
 * Listener to process request controller information.
 *
 * @package Drupal\minisite\EventSubscriber
 */
class AliasSubscriber implements EventSubscriberInterface {

  /**
   * Set Minisite delivery controller if request URI matches asset alias.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event that is created to create a response for a request.
   */
  public function onRequestSetController(GetResponseEvent $event) {
    // Do not alter non-master request (this is a case when an exception is
    // thrown in controller).
    if (!$event->isMasterRequest()) {
      return;
    }

    $request = $event->getRequest();

    // Load asset by the request URI which is an asset alias. This call must
    // be as "lightweight" as possible as it will run before any other routes
    // are considered (it is still faster to run this before all RouterListener
    // processing).
    $parsed_uri = UrlHelper::parse($request->getRequestUri());
    $asset = Asset::loadByAlias($parsed_uri['path']);
    if ($asset) {
      $request->attributes->set('_controller', '\Drupal\minisite\Controller\AliasController::deliverAsset');
      $request->attributes->set('asset_id', $asset->id());
      // Stop further propagation as our raw URL has matched.
      $event->stopPropagation();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The RouterListener has priority 32, and we need to run before that
    // because we are assessing raw URL path (we do not have a route for
    // asset aliases).
    $events[KernelEvents::REQUEST][] = ['onRequestSetController', 33];

    return $events;
  }

}
