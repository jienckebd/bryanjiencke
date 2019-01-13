<?php

namespace Drupal\bd_core\Entity;

use Drupal\Core\Entity\EntityTypeManager as Base;

/**
 * Extends core entity type manager.
 */
class EntityTypeManager extends Base implements EntityTypeManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    $this->normalizeEntityType($definitions);
    return $definitions;
  }

  /**
   * Normalize the entity type definitions.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The entity types.
   */
  protected function normalizeEntityType(array &$entity_types) {
    if (!$entity_type_config = $this->getEntityTypeConfig()) {
      return;
    }

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (empty($entity_type_config[$entity_type_id])) {
        continue;
      }
      $entity_type->set('normalize', $entity_type_config[$entity_type_id]);
    }

    $this->normalizeEntityTypeInit($entity_types);
    $this->normalizeEntityTypeHandler($entity_types);
    $this->normalizeEntityTypeRouting($entity_types);
  }

  /**
   * Normalize the entity type definitions.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The entity types.
   */
  protected function normalizeEntityTypeInit(array &$entity_types) {
  }

  /**
   * Normalize the entity type definitions.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The entity types.
   */
  protected function normalizeEntityTypeHandler(array &$entity_types) {
  }

  /**
   * Normalize the entity type definitions.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The entity types.
   */
  protected function normalizeEntityTypeRouting(array &$entity_types) {
  }

  /**
   * @return array|mixed|null
   */
  public function getEntityTypeConfig() {
    $config = \Drupal::configFactory()->getEditable('bd_core.settings');
    return $config->get('entity_type');
  }

}
