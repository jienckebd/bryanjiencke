<?php

namespace Drupal\cn_feeds\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Entity\Feed as Base;

class Feed extends Base implements FeedInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['fid']->setDisplayConfigurable('view', TRUE);
    $fields['type']->setDisplayConfigurable('view', TRUE);
    $fields['title']->setDisplayConfigurable('view', TRUE);
    $fields['uid']->setDisplayConfigurable('view', TRUE);
    $fields['status']->setDisplayConfigurable('view', TRUE);
    $fields['changed']->setDisplayConfigurable('view', TRUE);
    $fields['created']->setDisplayConfigurable('view', TRUE);
    $fields['item_count']->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
