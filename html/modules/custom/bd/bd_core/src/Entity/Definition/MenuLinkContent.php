<?php

namespace Drupal\bd_core\Entity\Definition;

use Drupal\menu_link_content\Entity\MenuLinkContent as Base;

/**
 * Provides a generic normalized entity class.
 */
class MenuLinkContent extends Base implements NormalizedContentEntityInterface {

  use NormalizedContentEntityTrait;

}
