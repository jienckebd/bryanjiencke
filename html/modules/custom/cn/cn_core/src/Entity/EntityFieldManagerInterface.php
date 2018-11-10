<?php

namespace Drupal\cn_core\Entity;

use Drupal\Core\Entity\EntityFieldManagerInterface as Base;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for an entity field manager.
 */
interface EntityFieldManagerInterface extends Base {

  /**
   * @param EntityInterface $entity
   * @param $field_name
   * @return array
   */
  public function newFieldValues(EntityInterface $entity_a, EntityInterface $entity_b, $field_name);

  /**
   * @param EntityInterface $entity
   * @param $field_name
   * @return array
   */
  public function removedFieldValues(EntityInterface $entity_a, EntityInterface $entity_b, $field_name);

  /**
   * @param EntityInterface $entity
   * @param string $field_name
   * @param string $value
   * @return array
   */
  public function hasFieldValue(EntityInterface $entity, $field_name, $value);

}
