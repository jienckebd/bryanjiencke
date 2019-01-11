<?php

namespace Drupal\bd_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replace core and contrib services and provide new ones.
 */
class BdCoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('route_enhancer.entity')) {
      $definition = $container->getDefinition('route_enhancer.entity');
      $definition->setClass(\Drupal\bd_core\Routing\EntityRouteEnhancer::class);
    }
    if ($container->has('entity_field.manager')) {
      $definition = $container->getDefinition('entity_field.manager');
      $definition->setClass(\Drupal\bd_core\Entity\EntityFieldManager::class);
    }
  }

}
