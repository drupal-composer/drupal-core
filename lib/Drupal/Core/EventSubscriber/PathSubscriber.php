<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @file
 *
 * Definition of Drupal\Core\EventSubscriber\AccessSubscriber
 */

/**
 * Access subscriber for controller requests.
 */
class PathSubscriber extends PathListenerAbstract implements EventSubscriberInterface {

  /**
   * Resolve the system path.
   *
   * @todo The path system should be objectified to remove the function calls
   * in this method.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestPathResolve(GetResponseEvent $event) {

    $request = $event->getRequest();

    $path = $this->extractPath($request);

    if (empty($path)) {
      // @todo Temporary hack. Fix when configuration is injectable.
      $path = variable_get('site_frontpage', 'user');
    }
    $system_path = drupal_get_normal_path($path);

    // Do our fancy frontpage logic.
    if (empty($system_path)) {
      $system_path = variable_get('site_frontpage', 'user');
    }

    $this->setPath($request, $system_path);
  }

  /**
   * Resolve the front-page default path.
   *
   * @todo The path system should be objectified to remove the function calls
   * in this method.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestFrontPageResolve(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path = $this->extractPath($request);

    if (empty($path)) {
      // @todo Temporary hack. Fix when configuration is injectable.
      $path = variable_get('site_frontpage', 'user');
    }

    $this->setPath($request, $path);
  }

  /**
   * Decodes the path of the request.
   *
   * Parameters in the URL sometimes represent code-meaningful strings. It is
   * therefore useful to always urldecode() those values so that individual
   * controllers need not concern themselves with it.  This is Drupal-specific
   * logic, and may not be familiar for developers used to other Symfony-family
   * projects.
   *
   * @todo Revisit whether or not this logic is appropriate for here or if
   *       controllers should be required to implement this logic themselves. If
   *       we decide to keep this code, remove this TODO.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestDecodePath(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path = $this->extractPath($request);

    $path = urldecode($path);

    $this->setPath($request, $path);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDecodePath', 102);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestFrontPageResolve', 101);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestPathResolve', 100);

    return $events;
  }
}
