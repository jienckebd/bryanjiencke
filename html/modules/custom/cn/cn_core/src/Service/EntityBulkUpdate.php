<?php

namespace Drupal\cn_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;

/**
 * Class Sys
 * @package Drupal\cn_core\Service
 */
class EntityBulkUpdate {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   * */
  public $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

  /**
   * The current user injected into the service.
   *
   * @var AccountInterface
   */
  public $currentUser;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  public $sessionManager;

  /**
   * Sys constructor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, AccountInterface $current_user, SessionManagerInterface $session_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
  }

  /**
   * @param $entity_type
   * @param array $bundles
   * @param array $field_value_mapping
   */
  public function byBundle($entity_type, array $bundles = [], array $field_value_mapping = []) {

    $entity_storage = $this->entityTypeManager
      ->getStorage($entity_type);
    $entity_definition = $this->entityTypeManager
      ->getDefinition($entity_type);

    $bundle_key = $entity_definition->getKey('bundle');
    foreach ($bundles as $bundle) {

      $entities = $entity_storage->loadByProperties([
        $bundle_key => $bundle,
      ]);

      foreach ($entities as $entity) {
        foreach ($field_value_mapping as $field_name => $field_values) {
          $entity->set($field_name, $field_values);
          $entity->save();
        }
      }

    }
  }

  /**
   * @param $entity_type
   * @param array $bundles
   * @param array $field_value_mapping
   */
  public function byEntityType($entity_type, array $field_value_mapping = []) {

    $entity_storage = $this->entityTypeManager
      ->getStorage($entity_type);
    $entity_definition = $this->entityTypeManager
      ->getDefinition($entity_type);

    $entities = $entity_storage->loadMultiple();

    foreach ($entities as $entity) {
      foreach ($field_value_mapping as $field_name => $field_values) {
        $entity->set($field_name, $field_values);
        $entity->save();
      }
    }
  }

}
