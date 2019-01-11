<?php

namespace Drupal\bd_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "episode_recent",
 *   admin_label = @Translation("Episode Recent"),
 *   category = @Translation("Talking Machines"),
 * )
 */
class EpisodeRecent extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'episode_recent',
      '#attached' => [
        'library' => ['tm_theme_alpha/art19'],
      ],
    ];
  }

}
