<?php

namespace Drupal\cn_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "ace_editor_juicy",
 *   label = @Translation("Ace Editor Juicy"),
 *   field_types = {
 *     "string_long",
 *   }
 * )
 */
class AceEditorJuicy extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // The text value has no text format assigned to it, so the user input
      // should equal the output, including newlines.
      $elements[$delta] = [
        '#theme' => 'juicy_ace_editor',
        '#code' => $item->value,
      ];
    }

    return $elements;
  }

}
