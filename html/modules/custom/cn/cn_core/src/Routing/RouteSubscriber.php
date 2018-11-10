<?php

namespace Drupal\cn_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('views.ajax')) {
      $route->setDefault('_controller', '\Drupal\cn_core\Controller\ViewsAjaxController::ajaxView');
    }

    $entity_form_routes = [
      'node.add',
      'entity.node.edit_form',
      'entity.taxonomy_term.add_form',
      'entity.taxonomy_term.edit_form',
      'user.admin_create',
      'entity.user.edit_form',
      'entity.media.add_form',
      'entity.media.edit_form',
    ];

    foreach ($entity_form_routes as $entity_form_route) {
      if ($route = $collection->get($entity_form_route)) {
        $route->setOption('_entity_form_route', 'TRUE');
      }
    }

    $entity_display_routes = [
      'entity.node.canonical',
      'entity.taxonomy_term.edit_form',
      'entity.user.canonical',
      'entity.media.canonical',
    ];

    foreach ($entity_display_routes as $entity_display_route) {
      if ($route = $collection->get($entity_display_route)) {
        $route->setOption('_entity_display_route', 'TRUE');
      }
    }

  }

}
