<?php

/**
 * @file
 * Contains views API hooks for Flag module.
 */

/**
 * Implements hook_views_data_alter().
 */
function cn_core_views_data_alter(array &$data) {
  $entity_type_manager = \Drupal::entityTypeManager();

  foreach ($entity_type_manager->getDefinitions() as $entity_type) {

    $entity_type_id = $entity_type->id();
    if (!$entity_type->hasHandlerClass('views_data')) {
      continue;
    }

    $base_table = $entity_type_manager->getHandler($entity_type_id, 'views_data')->getViewsTableForEntityType($entity_type);
    $data[$base_table]['related_relevancy'] = [
      'title' => t('@entity_label entity relationship relevancy', ['@entity_label' => $entity_type->getLabel()]),
      'help' => t('Sort by the relatedness to a given entity.'),
      'sort' => [
        'id' => 'entity_relationship_relevancy',
        'field' => $entity_type->getKey('id'),
      ],
    ];

    $data[$base_table]['not_current_entity'] = [
      'title' => t('@entity_label not current entity', ['@entity_label' => $entity_type->getLabel()]),
      'help' => t('Filters out the current entity.'),
      'filter' => [
        'id' => 'not_current_entity',
        'field' => $entity_type->getKey('id'),
      ],
    ];
  }

  $data['search_api_index_content']['entity_queue'] = [
    'title' => t('Entity Queue'),
    'help' => t('todo.'),
    'relationship' => [
      'id' => 'search_api_relationship',
      'title' => t('Entity Queue'),
      'label' => t('Entity Queue'),
      'base' => 'entity_subqueue__items',
      'base field' => 'items_target_id',
      'real field' => 'item_id',
      'relationship field' => 'item_id',
      'extra' => [
        [
          'table' => 'file_usage',
          'field' => 'type',
          'operator' => '=',
          'value' => 'taxonomy_term'
        ]
      ],
    ],
    'sort' => [
      'id' => 'entity_queue_position',
      'group' => t('Entityqueue'),
      'title' => t('@target_label Queue Position', [
        '@target_label' => 'Content',
      ]),
      'label' => t('@target_label Queue Position', [
        '@target_label' => 'Content',
      ]),
      'help' => t('Position of item in the @target_label queue.', [
        '@target_label' => 'Content',
      ]),
      'field' => 'delta',
      'field table' => 'entity_subqueue__items',
      'field_name' => 'items',
    ],
  ];

}

