<?php

namespace Drupal\bd_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "art_player",
 *   label = @Translation("Art19 Player"),
 *   field_types = {
 *     "link",
 *   }
 * )
 */
class Art19Player extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // The text value has no text format assigned to it, so the user input
      // should equal the output, including newlines.

      $uri = $item->uri;
      $episode_guid = str_replace('.mp3', '',  $uri);
      $episode_guid = str_replace('http://rss.art19.com/episodes/', '', $episode_guid);

      $elements[$delta] = [
        '#theme' => 'art19_player',
        '#guid' => $episode_guid,
        '#attached' => [
          'library' => [
            'tm_theme_alpha/art19',
          ],
        ],
      ];
    }

    return $elements;
  }

}
