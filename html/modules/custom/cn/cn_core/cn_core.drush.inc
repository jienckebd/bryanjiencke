<?php

/**
 * @file
 * Drush integration for the acquia connector module.
 */

use Drupal\acquia_connector\Controller\SpiController;
use Drupal\acquia_connector\Controller\TestStatusController;

/**
 * Implements hook_drush_command().
 */
function cn_core_drush_command() {
  $items = array();
  $items['cn-core-fix-nodes'] = [
  ];
  $items['cn-core-post-deploy'] = [
  ];
  return $items;
}

/**
 * Post deploy process.
 */
function drush_cn_core_post_deploy() {
  $entity_type_manager = \Drupal::entityTypeManager();

  $block_vote = $entity_type_manager
    ->getStorage('block_content')
    ->load(11);
  $block_vote_view = [
    'target_id' => 'node_tags_vote',
    'display_id' => 'block_1',
    'argument' => NULL,
    'title' => 0,
    'data' => NULL,
  ];
  $block_vote->set('field_view', $block_vote_view);
  $block_vote->save();

  $block_vote = $entity_type_manager
    ->getStorage('block_content')
    ->load(12);
  $block_vote_view = [
    'target_id' => 'node_related_blocks',
    'display_id' => 'block_1',
    'argument' => NULL,
    'title' => 0,
    'data' => NULL,
  ];
  $block_vote->set('field_view', $block_vote_view);
  $block_vote->save();
}

/**
 * @todo remove.
 */
function drush_cn_core_fix_nodes() {

  $seasons = [
    122 => strtotime('27 Apr 2017'),
    121 => strtotime('14 Jan 2016'),
  ];

  $podcasts = \Drupal::entityQuery('node')
    ->condition('type', 'podcast')
    ->sort('field_date', 'ASC')
    ->execute();

  $season_used = [
    120 => FALSE,
    121 => FALSE,
    122 => FALSE,
  ];

  $episode_number = 1;

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  foreach ($podcasts as $vid => $nid) {
    $node = $node_storage->load($nid);

    $episode_date = $node->field_date->value;
    $episode_timestamp = strtotime($episode_date);

    foreach ($seasons as $tid => $season_timestamp) {
      $season_tid = NULL;

      if ($episode_timestamp > $season_timestamp) {
        $season_tid = $node->field_season->target_id = $tid;
        if (!$season_used[$season_tid]) {
          $episode_number = 1;
        }
        $season_used[$season_tid] = TRUE;
        break;
      }
    }

    if (empty($season_tid)) {
      // Default to season 1.
      $season_tid = 120;
    }

    $node->field_season->target_id = $season_tid;
    $node->field_episode->value = $episode_number;
    $node->save();
    $episode_number++;
  }

}
