<?php

namespace Drupal\bd_core\Entity;

use Drupal\Core\Entity\EntityTypeManagerInterface as Base;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface for entity type managers.
 */
interface EntityTypeManagerInterface extends Base {

  /**
   * The subset types.
   *
   * @var array
   */
  const SUBSET_TYPE = [
    'content',
    'config',
    'bundle',
    'eck',
  ];

  /**
   * Get a subset of the entity types.
   *
   * @param string $subset_type
   *   The subset type.
   * @param array $entity_types
   *   The entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]|null
   *   The subset of entity types.
   */
  public function getDefinitionsSubset($subset_type, array &$entity_types = []);

  /**
   * Determines if a single entity type is in an entity type subset.
   *
   * @param string $subset_type
   *   The subset type.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return bool
   *   Whether or not the entity type is in a given subset.
   */
  public function isEntityTypeInSubset($subset_type, EntityTypeInterface $entity_type);

  /**
   * Get the normalize entity type config.
   *
   * @return array|mixed|null
   *   The entity type config.
   */
  public function getEntityTypeConfig();

}
