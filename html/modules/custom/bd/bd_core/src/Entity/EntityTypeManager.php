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

    if (!empty($normalize_config['subset'])) {
      foreach ($normalize_config['subset'] as $subset_type => $subset_normalize_config) {

        if (!$entity_type_subset = $this->getDefinitionsSubset($subset_type, $entity_types)) {
          continue;
        }

        // Either merge the subset config with existing normalize config or set
        // new normalize config for each entity type.
        foreach ($entity_type_subset as $entity_type_id => $entity_type) {
          if (!empty($normalize_config['definition'][$entity_type_id])) {
            $normalize_config['definition'][$entity_type_id] = array_merge_recursive($normalize_config['definition'][$entity_type_id], $subset_normalize_config);
          }
          else {
            $normalize_config['definition'][$entity_type_id] = $subset_normalize_config;
          }
        }

      }
    }

    // Once normalize config set for all entity types, process normalization.
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (empty($normalize_config['definition'][$entity_type_id])) {
        continue;
      }
      $entity_type->set('normalize', $normalize_config['definition'][$entity_type_id]);
      $this->normalizeEntityType($entity_type);
    }
  }

  /**
   * Wraps all methods to normalize a single entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  protected function normalizeEntityType(EntityTypeInterface $entity_type) {
    $normalize = $entity_type->get('normalize');
    $entity_type_id = $entity_type->id();

    // These data points will all be set at end of processing at once.
    $link_templates = $entity_type->getLinkTemplates();
    $entity_keys = $entity_type->getKeys();
    $handler_classes = $entity_type->getHandlerClasses();
    $revision_metadata_keys = $entity_type->get('revision_metadata_keys');

    // Set class override.
    if (!empty($normalize['class'])) {
      if (class_exists($normalize['class'])) {
        $entity_type->setClass($normalize['class']);
      }
    }

    // Process revisionable.
    if (!empty($normalize['revision']['make'])) {

      // Set revision storage.
      if (!$entity_type->get('revision_table')) {
        $entity_type->set('revision_table', "{$entity_type_id}_revision");
      }
      if (!$entity_type->get('revision_data_table')) {
        $entity_type->set('revision_data_table', "{$entity_type_id}_field_revision");
      }

      // Set entity and revision metadata keys.
      if (empty($entity_keys['revision'])) {
        $entity_keys['revision'] = 'revision_id';
      }

      $revision_metadata_keys_defaults = [
        'revision_log_message' => 'revision_log',
        'revision_created' => 'revision_timestamp',
        'revision_user' => 'revision_uid',
        'revision_default' => 'revision_default',
      ];
      foreach ($revision_metadata_keys_defaults as $revision_metadata_key => $revision_metadata_value) {
        if (empty($revision_metadata_keys[$revision_metadata_key])) {
          $revision_metadata_keys[$revision_metadata_key] = $revision_metadata_value;
        }
      }

      // Set revision link templates.
      if (empty($link_templates['revision'])) {
      }

      // Add revision route provider.
    }

    if (!empty($normalize['handler'])) {
      foreach ($normalize['handler'] as $handler_id => $handler_class) {
        if (class_exists($handler_class)) {
          $handler_classes[$handler_id] = $handler_class;
        }
      }
    }

    if (!empty($normalize['form'])) {
      foreach ($normalize['form'] as $form_op_id => $form_class) {
        if (class_exists($form_class)) {
          $handler_classes['form'][$form_op_id] = $form_class;
        }
      }
    }

    if (!empty($normalize['validation'])) {
      foreach ($normalize['validation'] as $validation_constraint_id => $validation_constraint_config) {
        $entity_type->addConstraint($validation_constraint_id, $validation_constraint_config);
      }
    }

    if (!empty($entity_keys)) {
      $entity_type->set('entity_keys', $entity_keys);
    }
    if (!empty($revision_metadata_keys)) {
      $entity_type->set('revision_metadata_keys', $revision_metadata_keys);
    }
    if (!empty($link_templates)) {
      $entity_type->set('links', $link_templates);
    }
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
