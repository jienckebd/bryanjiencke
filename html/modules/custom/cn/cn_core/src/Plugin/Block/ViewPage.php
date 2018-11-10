<?php

namespace Drupal\cn_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "view_page",
 *   admin_label = @Translation("View page"),
 *   category = @Translation("Talking Machines"),
 * )
 */
class ViewPage extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return views_embed_view('podcast_list', 'page_1');
  }

}
