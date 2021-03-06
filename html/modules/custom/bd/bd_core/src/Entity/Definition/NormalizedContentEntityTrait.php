<?php

namespace Drupal\bd_core\Entity\Definition;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\user\UserInterface;

/**
 * Trait to be injected in to a content entity class.
 */
trait NormalizedContentEntityTrait {

  use EntityPublishedTrait;
  use EntityChangedTrait;
  use RevisionLogEntityTrait;

  /**
   * Whether the node is being previewed or not.
   *
   * The variable is set to public as it will give a considerable performance
   * improvement. See https://www.drupal.org/node/2498919.
   *
   * @var true|null
   *   TRUE if the node is being previewed and NULL if it is not.
   */
  public $in_preview = NULL;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the node owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing node without adding a new revision, we
      // need to make sure $entity->revision_log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Update the node access table for this node, but only if it is the
    // default revision. There's no need to delete existing records if the node
    // is new.
    if ($this->isDefaultRevision()) {
      /** @var \Drupal\node\NodeAccessControlHandlerInterface $access_control_handler */
      $access_control_handler = \Drupal::entityManager()->getAccessControlHandler('node');
      $grants = $access_control_handler->acquireGrants($this);
      \Drupal::service('node.grant_storage')->write($this, $grants, NULL, $update);
    }

    // Reindex the node when it is updated. The node is automatically indexed
    // when it is added, simply by being added to the node table.
    if ($update) {
      node_reindex_node_search($this->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Ensure that all nodes deleted are removed from the search index.
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      foreach ($entities as $entity) {
        search_index_clear('node_search', $entity->nid->value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $nodes) {
    parent::postDelete($storage, $nodes);
    \Drupal::service('node.grant_storage')->deleteNodeRecords(array_keys($nodes));
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    // This override exists to set the operation to the default value "view".
    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPromoted() {
    return (bool) $this->get('promote')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPromoted($promoted) {
    $this->set('promote', $promoted ? NormalizedContentEntityInterface::PROMOTED : NormalizedContentEntityInterface::NOT_PROMOTED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSticky() {
    return (bool) $this->get('sticky')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSticky($sticky) {
    $this->set('sticky', $sticky ? NormalizedContentEntityInterface::STICKY : NormalizedContentEntityInterface::NOT_STICKY);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->getRevisionUser();
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->setRevisionUserId($uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $normalize = $entity_type->get('normalize');
    $entity_type_id = $entity_type->id();
    $entity_keys = $entity_type->getKeys();

    if (!empty($entity_keys['revision'])) {
      $fields += static::revisionLogBaseFieldDefinitions($entity_type);
    }

    if (!empty($normalize['field']['add'])) {

      if (in_array('uid', $normalize['field']['add'])) {
        $fields['uid'] = BaseFieldDefinition::create('entity_reference')
          ->setLabel(t('Authored by'))
          ->setDescription(t('The username of the content author.'))
          ->setRevisionable(TRUE)
          ->setSetting('target_type', 'user')
          ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
          ->setTranslatable(TRUE)
          ->setDisplayOptions('view', [
            'label' => 'hidden',
            'type' => 'author',
            'weight' => 0,
          ])
          ->setDisplayOptions('form', [
            'type' => 'entity_reference_autocomplete',
            'weight' => 5,
            'settings' => [
              'match_operator' => 'CONTAINS',
              'size' => '60',
              'placeholder' => '',
            ],
          ])
          ->setDisplayConfigurable('form', TRUE);
      }

      if (in_array('created', $normalize['field']['add'])) {
        $fields['created'] = BaseFieldDefinition::create('created')
          ->setLabel(t('Authored on'))
          ->setDescription(t('The time that the node was created.'))
          ->setRevisionable(TRUE)
          ->setTranslatable(TRUE)
          ->setDisplayOptions('view', [
            'label' => 'hidden',
            'type' => 'timestamp',
            'weight' => 0,
          ])
          ->setDisplayOptions('form', [
            'type' => 'datetime_timestamp',
            'weight' => 10,
          ]);
      }

      if (in_array('changed', $normalize['field']['add'])) {
        $fields['changed'] = BaseFieldDefinition::create('changed')
          ->setLabel(t('Changed'))
          ->setDescription(t('The time that the node was last edited.'))
          ->setRevisionable(TRUE)
          ->setTranslatable(TRUE);
      }

      if (in_array('promote', $normalize['field']['add'])) {
        $fields['promote'] = BaseFieldDefinition::create('boolean')
          ->setLabel(t('Promoted to front page'))
          ->setRevisionable(TRUE)
          ->setTranslatable(TRUE)
          ->setDefaultValue(TRUE)
          ->setDisplayOptions('form', [
            'type' => 'boolean_checkbox',
            'settings' => [
              'display_label' => TRUE,
            ],
            'weight' => 15,
          ])
          ->setDisplayConfigurable('form', TRUE);
      }

      if (in_array('sticky', $normalize['field']['add'])) {
        $fields['sticky'] = BaseFieldDefinition::create('boolean')
          ->setLabel(t('Sticky at top of lists'))
          ->setRevisionable(TRUE)
          ->setTranslatable(TRUE)
          ->setDefaultValue(FALSE)
          ->setDisplayOptions('form', [
            'type' => 'boolean_checkbox',
            'settings' => [
              'display_label' => TRUE,
            ],
            'weight' => 16,
          ])
          ->setDisplayConfigurable('form', TRUE);
      }

      if (in_array('weight', $normalize['field']['add'])) {
        $fields['weight'] = BaseFieldDefinition::create('integer')
          ->setLabel(t('Weight'))
          ->setDescription(t('The weight of this term in relation to other terms.'))
          ->setDefaultValue(0);
      }

      if (in_array('parent', $normalize['field']['add'])) {
        $fields['parent'] = BaseFieldDefinition::create('entity_reference')
          ->setLabel(t('Parents'))
          ->setDescription(t('The parents of this term.'))
          ->setSetting('target_type', 'taxonomy_term')
          ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);
      }

    }

    if (!empty($normalize['revision']['field'])) {
      foreach ($normalize['revision']['field'] as $field_name) {
        if (!empty($fields[$field_name])) {

          /** @var \Drupal\Core\Field\BaseFieldDefinition $field */
          $field = $fields[$field_name];
          $field->setRevisionable(TRUE);

        }
      }
    }

    $display_configurable_view_field = [];
    $display_configurable_form_field = [];
    if (!empty($entity_keys['published'])) {
      $display_configurable_form_field[] = $entity_keys['published'];
      $display_configurable_view_field[] = $entity_keys['published'];
    }
    if (!empty($entity_keys['label'])) {
      $display_configurable_form_field[] = $entity_keys['label'];
      $display_configurable_view_field[] = $entity_keys['label'];
    }
    if (!empty($entity_keys['bundle'])) {
      $display_configurable_view_field[] = $entity_keys['bundle'];
    }
    if (!empty($fields['created'])) {
      $display_configurable_form_field[] = 'created';
      $display_configurable_view_field[] = 'created';
    }
    if (!empty($fields['changed'])) {
      $display_configurable_view_field[] = 'changed';
    }
    if (!empty($fields['uid'])) {
      $display_configurable_form_field[] = 'uid';
      $display_configurable_view_field[] = 'uid';
    }

    foreach ($display_configurable_view_field as $field_name) {
      if (!empty($fields[$field_name])) {
        $fields[$field_name]->setDisplayConfigurable('view', TRUE);
      }
    }

    foreach ($display_configurable_form_field as $field_name) {
      if (!empty($fields[$field_name])) {
        $fields[$field_name]->setDisplayConfigurable('form', TRUE);
      }
    }

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
