<?php

namespace Drupal\cn_core\Plugin\views\relationship;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Views;

/**
 * Views relationship plugin for datasources.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("search_api_relationship")
 */
class SearchApi extends RelationshipPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The new entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['entity_queue']['default'] = FALSE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_type_manager = $this->getEntityTypeManager();

    $entity_queues = $entity_type_manager->getStorage('entity_queue')->loadMultiple();

    $options_entityqueue = [];
    foreach ($entity_queues as $entityqueue) {
      $options_entityqueue[$entityqueue->id()] = $entityqueue->label();
    }

    $form['entity_queue'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Queue'),
      '#description' => $this->t('Select an entity queue.'),
      '#default_value' => $this->options['entity_queue'],
      '#options' => $options_entityqueue,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Figure out what base table this relationship brings to the party.
    $table_data = Views::viewsData()->get($this->definition['base']);
    $base_field = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];

    $this->ensureMyTable();

    $def = $this->definition;
    $def['table'] = $this->definition['base'];
    $def['field'] = $base_field;
    $def['left_table'] = 'search_api_db_content';
    $def['left_field'] = $this->realField;
    $def['adjusted'] = TRUE;
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }
    $def['type'] = 'INNER';

    if (!empty($this->definition['extra'])) {
      $def['extra'] = $this->definition['extra'];
    }

    if (!empty($def['join_id'])) {
      $id = $def['join_id'];
    }
    else {
      $id = 'standard';
    }
    $join = Views::pluginManager('join')->createInstance($id, $def);

    $join = 'innerJoin';

    $alias = $def['table'] . '_' . $this->table;

    // $this->query->buildJoin($join);

    // $this->query->$join($def['table'], 'esi', $alias . ".item_id = CONCAT(\"entity:node/\", esi.items_target_id, \":en\") AND esi.bundle = 'homepage_arxiv'", NULL);

    $this->alias = $this->addRelationship($this->query, $join, $alias, $this->definition['base']);

    // Add access tags if the base table provide it.
    if (empty($this->query->options['disable_sql_rewrite']) && isset($table_data['table']['base']['access query tag'])) {
      $access_tag = $table_data['table']['base']['access query tag'];
      $this->query->addTag($access_tag);
    }
  }

  public function addRelationship($query, $join, $alias, $base) {
    if (empty($link_point)) {
      $link_point = $query->view->storage->get('base_table');
    }
    elseif (!array_key_exists($link_point, $query->relationships)) {
      return FALSE;
    }

    // Make sure $alias isn't already used; if it, start adding stuff.
    $alias_base = $alias;
    $count = 1;
    while (!empty($query->relationships[$alias])) {
      $alias = $alias_base . '_' . $count++;
    }

    // Make sure this join is adjusted for our relationship.
    if ($link_point && isset($this->relationships[$link_point])) {
      $join = $query->adjustJoin($join, $link_point);
    }

    // Add the table directly to the queue to avoid accidentally marking
    // it.
    $query->tableQueue[$alias] = [
      'table' => $join->table,
      'num' => 1,
      'alias' => $alias,
      'join' => $join,
      'relationship' => $link_point,
    ];

    $query->relationships[$alias] = [
      'link' => $link_point,
      'table' => $join->table,
      'base' => $base,
    ];

    $query->tables[$this->view->storage->get('base_table')][$alias] = [
      'count' => 1,
      'alias' => $alias,
    ];

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];

    if (!empty($this->definition['entity type'])) {
      $entity_type = $this->getEntityTypeManager()
        ->getDefinition($this->definition['entity type']);
      if ($entity_type) {
        $dependencies['module'][] = $entity_type->getProvider();
      }
    }

    return $dependencies;
  }

}
