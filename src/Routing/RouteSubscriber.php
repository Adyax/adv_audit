<?php

namespace Drupal\adv_audit\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.adv_audit.canonical')) {
      $route->setOption('_admin_route', TRUE);
    }

    if ($route = $collection->get('entity.adv_audit.collection')) {
      $route->setOption('_admin_route', TRUE);
    }
  }

}
