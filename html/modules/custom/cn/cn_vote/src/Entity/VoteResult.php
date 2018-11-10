<?php

namespace Drupal\cn_vote\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\votingapi\VoteResultInterface;
use Drupal\votingapi\Entity\VoteResult as Base;

/**
 * Class VoteResult
 * @package Drupal\cn_vote\Entity
 */
class VoteResult extends Base implements VoteResultInterface {
}
