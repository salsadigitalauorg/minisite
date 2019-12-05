<?php

namespace Drupal\minisite\EventSubscriber;

use Drupal\minisite\Asset;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class MinisiteSubscriber.
 *
 * Listener to process request controller information.
 *
 * @package Drupal\minisite\EventSubscriber
 */
class MinisiteSubscriber implements EventSubscriberInterface {

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

    $asset = Asset::loadByAlias($request->getRequestUri());
    if ($asset) {
      $request->attributes->set('_controller', '\Drupal\minisite\Controller\MinisiteController::deliverMinisiteAsset');
      $request->attributes->set('asset_id', $asset->id());
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
