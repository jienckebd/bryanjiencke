<?php

namespace Drupal\bd_core\Entity\Definition;

use Drupal\eck\Entity\EckEntity as Base;

/**
 * Provides a generic normalized entity class.
 */
class EckEntity extends Base implements NormalizedContentEntityInterface {

  use NormalizedContentEntityTrait;

}
