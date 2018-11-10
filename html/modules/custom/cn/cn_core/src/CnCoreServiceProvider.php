<?php

namespace Drupal\cn_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replace the resource type repository for our own configurable version.
 */
class CnCoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('route_enhancer.entity')) {
      $definition = $container->getDefinition('route_enhancer.entity');
      $definition->setClass(\Drupal\cn_core\Routing\EntityRouteEnhancer::class);
    }
    if ($container->has('entity_field.manager')) {
      $definition = $container->getDefinition('entity_field.manager');
      $definition->setClass(\Drupal\cn_core\Entity\EntityFieldManager::class);
    }
  }

}
