<?php

namespace Drupal\autoref;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a autoref entity.
 */
interface AutorefInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * Denotes that the autoref is not published.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Denotes that the autoref is published.
   */
  const PUBLISHED = 1;

  /**
   * Gets the autoref type.
   *
   * @return string
   *   The autoref type.
   */
  public function getType();

  /**
   * Gets the autoref label.
   *
   * @return string
   *   Label of the autoref.
   */
  public function getLabel();

  /**
   * Sets the autoref label.
   *
   * @param string $label
   *   The autoref label.
   *
   * @return \Drupal\autoref\AutorefInterface
   *   The called autoref entity.
   */
  public function setLabel($label);

  /**
   * Gets the autoref creation timestamp.
   *
   * @return int
   *   Creation timestamp of the autoref.
   */
  public function getCreatedTime();

  /**
   * Sets the autoref creation timestamp.
   *
   * @param int $timestamp
   *   The autoref creation timestamp.
   *
   * @return \Drupal\autoref\AutorefInterface
   *   The called autoref entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the autoref revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the autoref revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\autoref\AutorefInterface
   *   The called autoref entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the autoref revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\RevisionLogInterface::getRevisionUser() instead.
   */
  public function getRevisionAuthor();

  /**
   * Sets the autoref revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\autoref\AutorefInterface
   *   The called autoref entity.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\RevisionLogInterface::setRevisionUserId() instead.
   */
  public function setRevisionAuthorId($uid);

}
