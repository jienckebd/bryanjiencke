<?php

namespace Drupal\cn_vote\Entity;

use Drupal\votingapi\VoteInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\votingapi\Entity\Vote as Base;

/**
 * Class Vote
 * @package Drupal\cn_vote\Entity
 */
class Vote extends Base implements VoteInterface {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
  }

}
