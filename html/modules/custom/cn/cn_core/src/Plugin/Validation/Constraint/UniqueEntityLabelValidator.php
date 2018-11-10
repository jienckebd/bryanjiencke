<?php

namespace Drupal\cn_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueFieldValue constraint.
 */
class UniqueEntityLabelValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    $entity_type_manager = \Drupal::entityTypeManager();

    $entity_id = $entity->id();
    $entity_label = $entity->label();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_bundle_id = $entity->bundle();

    $entity_storage = $entity_type_manager
      ->getStorage($entity_type_id);

    $entity_definition = $entity_type_manager
      ->getDefinition($entity_type_id);

    $entity_id_key = $entity_definition->getKey('id');
    $entity_label_key = $entity_definition->getKey('label');
    $entity_bundle_key = $entity_definition->getKey('bundle');

    $entities_with_label_query = $entity_storage->getQuery()
      ->condition($entity_label_key, $entity_label);

    if (!empty($entity_id)) {
      $entities_with_label_query->condition($entity_id_key, $entity_id, '!=');
    }

    // Only validate within this bundle.
    if (!empty($entity_bundle_key) && !empty($entity_bundle_id)) {
      $entities_with_label_query->condition($entity_bundle_key, $entity_bundle_id);
    }

    $entities_with_label = $entities_with_label_query->execute();

    if (!empty($entities_with_label)) {
      $this->context->addViolation($constraint->notUnique, [
        '%value' => $entity_label,
        '%entity_type_label' => $entity_definition->getSingularLabel(),
      ]);
    }

  }

}
