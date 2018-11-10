<?php

namespace Drupal\autoref\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\autoref\AutorefTypeInterface;

/**
 * Defines the Autoref type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "autoref_type",
 *   label = @Translation("Autoref Type"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\autoref\Form\AutorefTypeForm",
 *       "edit" = "Drupal\autoref\Form\AutorefTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\autoref\ListBuilder\AutorefTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\autoref\Routing\AutorefTypeRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer autoref",
 *   config_prefix = "type",
 *   bundle_of = "autoref",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/autoref/add",
 *     "edit-form" = "/admin/structure/autoref/manage/{autoref_type}",
 *     "delete-form" = "/admin/structure/autoref/manage/{autoref_type}/delete",
 *     "collection" = "/admin/structure/autoref",
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *     "help",
 *     "new_revision"
 *   }
 * )
 */
class AutorefType extends ConfigEntityBundleBase implements AutorefTypeInterface {

  /**
   * The machine name of this autoref type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the autoref type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this autoref type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating a Autoref of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * Default value of the 'Create new revision' checkbox of this autoref type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * Display setting for author and date Submitted by post information.
   *
   * @var bool
   */
  protected $display_submitted = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('autoref.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the autoref type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->isNewRevision();
  }

}
