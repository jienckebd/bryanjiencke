<?php

namespace Drupal\cn_core\Plugin\views\filter;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by term id.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_reference_options")
 */
class EntityReferenceOptions extends ManyToOne {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {

    $feeds = \Drupal::entityTypeManager()->getStorage('feeds_feed')->loadMultiple();

    $options = [];

    foreach ($feeds as $key => $feed) {
      $options[$feed->id()] = $feed->label();
    }

    $this->valueOptions = $options;

    return $this->valueOptions;
  }

}
