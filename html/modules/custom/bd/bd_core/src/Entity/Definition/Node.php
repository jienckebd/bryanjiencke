<?php

namespace Drupal\bd_core\Entity\Definition;

use Drupal\node\Entity\Node as Base;

/**
 * Provides a generic normalized entity class.
 */
class Node extends Base implements NormalizedContentEntityInterface {

  use NormalizedContentEntityTrait;

}
