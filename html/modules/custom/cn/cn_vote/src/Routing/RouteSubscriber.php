<?php

namespace Drupal\cn_vote\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RouteSubscriberBase;

/**
 * Alter the node view route.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change the contact_form controller.
    if ($route = $collection->get('vud.vote.context')) {
      $route->setDefault('_controller', '\Drupal\cn_vote\Controller\VotingApiController::vote');
    }

    if ($route = $collection->get('vud.reset.context')) {
      $route->setDefault('_controller', '\Drupal\cn_vote\Controller\VotingApiController::resetVote');
    }
  }

}
