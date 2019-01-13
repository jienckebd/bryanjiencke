<?php

namespace Drupal\bd_core\Entity\Definition;

use Drupal\taxonomy\Entity\Term as Base;

/**
 * Provides a generic normalized entity class.
 */
class Term extends Base implements NormalizedContentEntityInterface {

  use NormalizedContentEntityTrait;

}
