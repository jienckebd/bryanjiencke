<?php

namespace Drupal\bd_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\bd_core\Routing\EntityRouteEnhancer;
use Drupal\bd_core\Entity\EntityFieldManager;
use Drupal\bd_core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Replace core and contrib services and provide new ones.
 */
class BdCoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    $definition = $container->getDefinition('entity_type.manager');
    $definition->setClass(EntityTypeManager::class);

    $definition = $container->getDefinition('entity_field.manager');
    $definition->setClass(EntityFieldManager::class);

    $definition = $container->getDefinition('route_enhancer.entity');
    $definition->setClass(EntityRouteEnhancer::class);

  }

}
