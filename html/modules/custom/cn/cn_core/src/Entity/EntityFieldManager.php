<?php

namespace Drupal\cn_core\Entity;

use Drupal\Core\Entity\EntityFieldManager as Base;
use Drupal\Core\Entity\EntityInterface;

/**
 * Manages the discovery of entity fields.
 *
 * This includes field definitions, base field definitions, and field storage
 * definitions.
 */
class EntityFieldManager extends Base implements EntityFieldManagerInterface {

  /**
   * @param EntityInterface $entity_a
   * @param EntityInterface $entity_b
   * @param $field_name
   * @return array
   */
  public function newFieldValues(EntityInterface $entity_a, EntityInterface $entity_b, $field_name) {
    $new_field_values = $entity_a->get($field_name)->getValue();
    $new_values = [];
    foreach ($new_field_values as $key => $field_value) {
      $new_values[$key] = $field_value['target_id'];
    }

    $original_values = [];
    $original_field_values = $entity_b->get($field_name)->getValue();
    foreach ($original_field_values as $key => $field_value) {
      $original_values[$key] = $field_value['target_id'];
    }

    return array_diff($original_values, $new_values);
  }

  /**
   * @param EntityInterface $entity_a
   * @param EntityInterface $entity_b
   * @param $field_name
   * @return array
   */
  public function removedFieldValues(EntityInterface $entity_a, EntityInterface $entity_b, $field_name) {
    $new_field_values = $entity_a->get($field_name)->getValue();
    $new_values = [];
    foreach ($new_field_values as $key => $field_value) {
      $new_values[$key] = $field_value['target_id'];
    }

    $original_values = [];
    $original_field_values = $entity_b->get($field_name)->getValue();
    foreach ($original_field_values as $key => $field_value) {
      $original_values[$key] = $field_value['target_id'];
    }

    return array_diff($original_values, $new_values);
  }

  /**
   * Check that a field has a given field value.
   *
   * @param EntityInterface $entity
   * @param string $field_name
   * @param string $value
   */
  public function hasFieldValue(EntityInterface $entity, $field_name, $value) {
  }

}
