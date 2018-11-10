<?php

namespace Drupal\cn_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class Sys
 * @package Drupal\cn_core\Service
 */
class DefaultFieldValue {

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
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache, AccountInterface $current_user, SessionManagerInterface $session_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->cache = $cache;
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
  }

  /**
   * Set default values.
   *
   * @param array $entities
   */
  public function setDefaults(array &$entities = []) {

    // Validate that the path is not blacklisted.
    $current_path = \Drupal::service('path.current')->getPath();
    $ignore_path_patterns = [
      '/node/*/edit',
    ];
    foreach ($ignore_path_patterns as $ignore_path_pattern) {
      if (fnmatch($ignore_path_pattern, $current_path)) {
        return;
      }
    }

    $default_config = $this->buildDefaultConfig();

    foreach ($entities as $entity) {
      if ($entity->hasField('field_image')
        && $entity->get('field_image')->isEmpty()) {

        $entity_ids = [];
        if ($entity->hasField('field_tags')) {
          foreach ($entity->get('field_tags')->getValue() as $key => $data) {
            $entity_ids[] = $data['target_id'];
          }
        }

        $common_entity_ids = array_intersect($entity_ids, array_keys($default_config['tag_data']));
        if (!empty($common_entity_ids) && !empty($common_entity_ids[0])) {
          $media_entity_id = $default_config['tag_data'][$common_entity_ids[0]]['entity_id'];
        }

        if (empty($media_entity_id)) {
          $default_media_id_key = rand($default_config['min'], $default_config['max']);
          $media_entity_id = $default_config['media_pool'][$default_media_id_key];
        }
        $media_values = [
          'target_id' => $media_entity_id,
        ];
        $entity->set('field_image', $media_values);
      }
    }

  }

  /**
   * Builds the default field value config.
   *
   * @return array
   */
  public function buildDefaultConfig() {

    $cid = 'default_field_value_pool';
    if ($cache_item = $this->cache->get($cid)) {
      $data = $cache_item->data;
    }
    else {

      $database = \Drupal::database();
      $query = $database->select('media__field_tags', 'mft');
      $query->join('media_field_data', 'mfd', 'mft.entity_id = mfd.mid');
      $query->fields('mft', ['entity_id', 'field_tags_target_id']);
      $query->condition('mft.deleted', 0);
      $query->condition('mfd.status', 1);
      $tag_data = $query->execute()->fetchAllAssoc('field_tags_target_id');

      foreach ($tag_data as $key => $datum) {
        $tag_data[$key] = (array)$datum;
      }

      $query = \Drupal::entityQuery('media');
      $query->condition('status', 1);
      $query->condition('field_default', 1);
      $query->range(0, 20);
      $query->addTag('sort_by_random');
      $default_entity_ids = $query->execute();

      if (!empty($default_entity_ids)) {
        $defaults_keyed = [];
        foreach ($default_entity_ids as $revision_id => $entity_id) {
          $defaults_keyed[] = $entity_id;
        }
      }

      $total = count($defaults_keyed);
      $min = 0;
      $max = $total - 1;

      $data = [
        'tag_data' => $tag_data,
        'media_pool' => $defaults_keyed,
        'min' => $min,
        'max' => $max,
      ];
      $this->cache->set($cid, $data);
    }

    return $data;
  }

}
