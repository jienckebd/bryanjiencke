<?php

namespace Drupal\bd_core\Entity;

use Drupal\Core\Entity\EntityTypeManager as Base;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;

/**
 * Extends core entity type manager.
 */
class EntityTypeManager extends Base implements EntityTypeManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    $this->normalizeDefinitions($definitions);
    return $definitions;
  }

  /**
   * Normalize the entity type definitions.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The entity types.
   */
  protected function normalizeDefinitions(array &$entity_types) {
    $entity_type_config = $this->getEntityTypeConfig();

    if (empty($entity_type_config['normalize'])) {
      return;
    }

    $normalize_config = &$entity_type_config['normalize'];

    // Explicitly set normalize config for an entity type takes precedence. So
    // set this first and then process subsets.
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (empty($normalize_config['override'][$entity_type_id])) {
        continue;
      }
      $entity_type->set('normalize', $normalize_config[$entity_type_id]);
    }

    if (!empty($normalize_config['subset'])) {
      foreach ($normalize_config['subset'] as $subset_type => $subset_normalize_config) {

        if (!$entity_type_subset = $this->getDefinitionsSubset($subset_type, $entity_types)) {
          continue;
        }

        foreach ($entity_type_subset as $entity_type_id => $entity_type) {
          $this->mergeNormalizeConfig($entity_type, $subset_normalize_config);
        }

      }
    }

    // Once normalize config set for all entity types, process normalization.
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$normalize = $entity_type->get('normalize')) {
        continue;
      }
      $this->normalizeEntityType($entity_type);
    }
  }

  /**
   * Merge normalize config settings before processing normalization.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param array $normalize_config
   *   The normalize config.
   */
  protected function mergeNormalizeConfig(EntityTypeInterface $entity_type, array &$normalize_config) {
    $entity_type_normalize_config = $entity_type->get('normalize') ?: [];
    $entity_type_normalize_config = array_merge_recursive($entity_type_normalize_config, $normalize_config);
    $entity_type->set('normalize', $entity_type_normalize_config);
  }

  /**
   * Wraps all methods to normalize a single entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  protected function normalizeEntityType(EntityTypeInterface $entity_type) {
    $this->normalizeEntityTypeInit($entity_type);
    $this->normalizeEntityTypeHandler($entity_type);
    $this->normalizeEntityTypeRouting($entity_type);
  }

  /**
   * Normalize the entity type definitions.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  protected function normalizeEntityTypeInit(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $normalize = $entity_type->get('normalize');

    if ($this->isEntityTypeInSubset('content', $entity_type)) {
      $entity_type->setClass(NormalizedContentEntity::class);

      if (!empty($normalize['revision']['make'])) {
        $entity_keys = $entity_type->getKeys();
        $entity_keys['revision'] = 'revision_id';
        $entity_type->set('entity_keys', $entity_keys);

        $entity_type->set('revision_table', "{$entity_type_id}_revision");
        $entity_type->set('revision_data_table', "{$entity_type_id}_field_revision");

        $revision_metadata_keys = [
          'revision_log_message' => 'revision_log',
          'revision_created' => 'revision_timestamp',
          'revision_user' => 'revision_uid',
          'revision_default' => 'revision_default',
        ];
        $entity_type->set('revision_metadata_keys', $revision_metadata_keys);
      }

    }

    if (!empty($normalize['handler'])) {
      foreach ($normalize['handler'] as $handler_id => $handler_class) {
        if (class_exists($handler_class)) {
          $entity_type->setHandlerClass($handler_id, $handler_class);
        }
      }
    }

    if (!empty($normalize['form'])) {
      foreach ($normalize['form'] as $form_op_id => $form_class) {
        if (class_exists($form_class)) {
          $entity_type->setFormClass($form_op_id, $form_class);
        }
      }
    }

    if (!empty($normalize['validation'])) {
      foreach ($normalize['validation'] as $validation_constraint_id => $validation_constraint_config) {
        $entity_type->addConstraint($validation_constraint_id, $validation_constraint_config);
      }
    }
  }

  /**
   * Normalize the entity type definitions.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  protected function normalizeEntityTypeHandler(EntityTypeInterface $entity_type) {
    $normalize = $entity_type->get('normalize');
  }

  /**
   * Normalize routing and link templates.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  protected function normalizeEntityTypeRouting(EntityTypeInterface $entity_type) {
    $normalize = $entity_type->get('normalize');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsSubset($subset_type, array &$entity_types = []) {
    if (empty($entity_types)) {
      $entity_types = $this->findDefinitions();
    }

    $entity_types_subset = [];

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($this->isEntityTypeInSubset($subset_type, $entity_type)) {
        $entity_types_subset[$entity_type_id] = $entity_type;
      }
    }

    return $entity_types_subset;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityTypeInSubset($subset_type, EntityTypeInterface $entity_type) {
    $return = FALSE;

    switch ($subset_type) {

      case 'content':
        if ($entity_type instanceof ContentEntityTypeInterface) {
          $return = TRUE;
        }
        break;

      case 'config':
        if ($entity_type instanceof ConfigEntityTypeInterface) {
          $return = TRUE;
        }
        break;

      case 'bundle':
        if ($entity_type instanceof ConfigEntityTypeInterface && $entity_type->getBundleOf()) {
          $return = TRUE;
        }
        break;

    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeConfig() {
    $config = \Drupal::configFactory()->getEditable('bd_core.settings');
    return $config->get('entity_type') ?: [];
  }

}
