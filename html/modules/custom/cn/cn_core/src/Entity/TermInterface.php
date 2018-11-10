<?php

namespace Drupal\cn_core\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\taxonomy\TermInterface as Base;

/**
 * Provides an interface defining a node entity.
 */
interface TermInterface extends Base, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Denotes that the node is not published.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Denotes that the node is published.
   */
  const PUBLISHED = 1;

  /**
   * Denotes that the node is not promoted to the front page.
   */
  const NOT_PROMOTED = 0;

  /**
   * Denotes that the node is promoted to the front page.
   */
  const PROMOTED = 1;

  /**
   * Denotes that the node is not sticky at the top of the page.
   */
  const NOT_STICKY = 0;

  /**
   * Denotes that the node is sticky at the top of the page.
   */
  const STICKY = 1;

  /**
   * Gets the node creation timestamp.
   *
   * @return int
   *   Creation timestamp of the node.
   */
  public function getCreatedTime();

  /**
   * Sets the node creation timestamp.
   *
   * @param int $timestamp
   *   The node creation timestamp.
   *
   * @return \Drupal\node\NodeInterface
   *   The called node entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the node promotion status.
   *
   * @return bool
   *   TRUE if the node is promoted.
   */
  public function isPromoted();

  /**
   * Sets the node promoted status.
   *
   * @param bool $promoted
   *   TRUE to set this node to promoted, FALSE to set it to not promoted.
   *
   * @return \Drupal\node\NodeInterface
   *   The called node entity.
   */
  public function setPromoted($promoted);

  /**
   * Returns the node sticky status.
   *
   * @return bool
   *   TRUE if the node is sticky.
   */
  public function isSticky();

  /**
   * Sets the node sticky status.
   *
   * @param bool $sticky
   *   TRUE to set this node to sticky, FALSE to set it to not sticky.
   *
   * @return \Drupal\node\NodeInterface
   *   The called node entity.
   */
  public function setSticky($sticky);

  /**
   * Gets the node revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\RevisionLogInterface::getRevisionUser() instead.
   */
  public function getRevisionAuthor();

  /**
   * Sets the node revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\node\NodeInterface
   *   The called node entity.
   *
   * @deprecated in Drupal 8.2.0, will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Entity\RevisionLogInterface::setRevisionUserId() instead.
   */
  public function setRevisionAuthorId($uid);

}
