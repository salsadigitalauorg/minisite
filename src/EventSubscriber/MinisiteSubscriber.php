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
    // The RouterListener has priority 32, and we need to run after that.
    $events[KernelEvents::REQUEST][] = ['onRequestSetController', 30];

    return $events;
  }

}
