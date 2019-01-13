<?php

namespace Drupal\attribute;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a attribute entity.
 */
interface AttributeInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * Denotes that the attribute is not published.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Denotes that the attribute is published.
   */
  const PUBLISHED = 1;

  /**
   * Gets the attribute type.
   *
   * @return string
   *   The attribute type.
   */
  public function getType();

  /**
   * Gets the attribute label.
   *
   * @return string
   *   Label of the attribute.
   */
  public function getLabel();

  /**
   * Sets the attribute label.
   *
   * @param string $label
   *   The attribute label.
   *
   * @return \Drupal\attribute\AttributeInterface
   *   The called attribute entity.
   */
  public function setLabel($label);

  /**
   * Gets the attribute creation timestamp.
   *
   * @return int
   *   Creation timestamp of the attribute.
   */
  public function getCreatedTime();

  /**
   * Sets the attribute creation timestamp.
   *
   * @param int $timestamp
   *   The attribute creation timestamp.
   *
   * @return \Drupal\attribute\AttributeInterface
   *   The called attribute entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the attribute revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the attribute revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\attribute\AttributeInterface
   *   The called attribute entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the attribute revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\RevisionLogInterface::getRevisionUser() instead.
   */
  public function getRevisionAuthor();

  /**
   * Sets the attribute revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\attribute\AttributeInterface
   *   The called attribute entity.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\RevisionLogInterface::setRevisionUserId() instead.
   */
  public function setRevisionAuthorId($uid);

}
