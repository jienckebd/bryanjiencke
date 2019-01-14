<?php

namespace Drupal\bd_core\Entity\Definition;

use Drupal\activity_creator\Entity\Activity as Base;

/**
 * Provides a generic normalized entity class.
 */
class Activity extends Base implements NormalizedContentEntityInterface {

  use NormalizedContentEntityTrait;

}
