<?php

namespace Drupal\bd_core\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;

/**
 * Provides a generic normalized entity class.
 */
class NormalizedContentEntity extends EditorialContentEntityBase implements NormalizedContentEntityInterface {

  use NormalizedContentEntityTrait;

}
