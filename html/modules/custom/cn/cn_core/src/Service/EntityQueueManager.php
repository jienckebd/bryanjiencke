<?php

namespace Drupal\cn_core\Service;

use Drupal\cn_core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entityqueue\EntitySubqueueInterface;

/**
 * Class EntityQueueManager
 * @package Drupal\cn_core\Service
 */
class EntityQueueManager {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   * */
  public $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public $entityFieldManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  public $cache;

  /**
   * The current user injected into the service.
   *
   * @var AccountInterface
   */
  public $currentUser;

  /**
   * EntityQueueManager constructor.
   * @param EntityTypeManagerInterface $entity_type_manager
   * @param EntityFieldManagerInterface $entity_field_manager
   * @param ConfigFactoryInterface $config_factory
   * @param CacheBackendInterface $cache
   * @param AccountInterface $current_user
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->currentUser = $current_user;
  }

  /**
   * @param EntitySubqueueInterface $entity_subqueue
   */
  public function processQueue(EntitySubqueueInterface $entity_subqueue) {

    // Iterate through all queue items.
    foreach ($entity_subqueue->get('items')->getValue() as $item_data) {

      // Get target entity type of queue.

      $entity_storage = $this->entityTypeManager->getStorage('node');
      $queued_entity = $entity_storage->load($item_data['target_id']);

      // If entity has field field_entity_queue, confirm this entity is in it.
      if (!empty($queued_entity) && $queued_entity->hasField('field_entity_queue')) {

        $has_queue = FALSE;
        foreach ($queued_entity->get('field_entity_queue')->getValue() as $related_queue_data) {
          if ($entity_subqueue->id() == $related_queue_data['target_id']) {
            $has_queue = TRUE;
          }
        }

        if (!$has_queue) {
          $queued_entity->get('field_entity_queue')->appendItem($entity_subqueue->id());
          $queued_entity->save();
        }
      }
    }

    // If this is an update to an existing queue, check for removed items in
    // the original and update field_entity_queue on these entities.
    if (!empty($entity_subqueue->original) && is_object($entity_subqueue->original)) {
      foreach ($this->entityFieldManager->removedFieldValues($entity_subqueue, $entity_subqueue->original, 'items') as $removed_field_value) {
        $entity_storage = $this->entityTypeManager->getStorage('node');
        $queued_entity = $entity_storage->load($removed_field_value);

        // If entity has field field_entity_queue, confirm this entity is in it.
        if (!empty($queued_entity) && $queued_entity->hasField('field_entity_queue')) {
          foreach ($queued_entity->get('field_entity_queue')->getValue() as $delta => $entity_queue_data) {
            if ($entity_subqueue->id() == $entity_queue_data['target_id']) {
              $queued_entity->get('field_entity_queue')->removeItem($delta);
              $queued_entity->save();
              break;
            }
          }
        }
      }
    }

  }

  /**
   * @param EntityInterface $entity
   */
  public function processEntity(EntityInterface $entity) {

    $entity_subqueue_storage = \Drupal::entityTypeManager()->getStorage('entity_subqueue');

    $new_field_values = $entity->get('field_entity_queue')->getValue();
    $new_values = [];
    foreach ($new_field_values as $field_value) {
      $new_values[] = $field_value['target_id'];
    }

    $original_values = [];
    if (!empty($entity->original) && is_object($entity->original)) {
      $original_field_values = $entity->original->get('field_entity_queue')->getValue();
      foreach ($original_field_values as $field_value) {
        $original_values[] = $field_value['target_id'];
      }
    }

    foreach ($new_values as $key => $target_id) {
      $query = \Drupal::entityQuery('entity_subqueue')->condition('queue', $target_id);
      $result = $query->execute();
      $subqueues = $entity_subqueue_storage->loadMultiple($result);

      // Check if the entity is referenced in a subqueue.
      foreach ($subqueues as $subqueue) {
        $items = $subqueue->get('items')->getValue();
        if (($item_key = array_search($entity->id(), array_column($items, 'target_id'))) === FALSE) {
          array_unshift($items, $entity->id());
          $subqueue->set('items', $items);
          $subqueue->save();
        }
      }
    }

    $removed_values = array_diff($original_values, $new_values);
    if (!empty($removed_values)) {
      foreach ($removed_values as $target_id) {
        $query = \Drupal::entityQuery('entity_subqueue')->condition('queue', $target_id);
        $result = $query->execute();
        $subqueues = $entity_subqueue_storage->loadMultiple($result);

        // Check if the entity is referenced in a subqueue.
        foreach ($subqueues as $subqueue) {
          $items = $subqueue->get('items')->getValue();
          if (($item_key = array_search($entity->id(), array_column($items, 'target_id'))) !== FALSE) {
            unset($items[$item_key]);
            $subqueue->set('items', $items);
            $subqueue->save();
          }
        }
      }
    }

  }

}
