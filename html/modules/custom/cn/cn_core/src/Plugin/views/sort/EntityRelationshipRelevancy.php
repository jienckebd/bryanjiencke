<?php

namespace Drupal\cn_core\Plugin\views\sort;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\Standard as Base;

/**
 * Default implementation of the base sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("entity_relationship_relevancy")
 */
class EntityRelationshipRelevancy extends Base implements CacheableDependencyInterface {

  /**
   * {inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $field_name = $this->options['field_name'];
    $value_field = $this->options['value_field'];

    $entity = \Drupal::routeMatch()->getParameter('node');
    if ($entity->hasField($field_name)) {
      $entity_ids = [];
      $field_values = $entity->get($field_name)->getValue();
      foreach ($field_values as $key => $data) {
        if (!empty($data[$value_field])) {
          $entity_ids[] = $data[$value_field];
        }
      }


    }

    // Add the field.
    // $this->query->addOrderBy($this->tableAlias, $this->realField, $this->options['order']);
  }

  /**
   * {inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['arg'] = ['default' => 'ASC'];
    $options['entity_type'] = ['default' => FALSE];
    $options['entity_id'] = ['default' => FALSE];
    $options['field_name'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_type_manager = \Drupal::entityTypeManager();

    $form['entity_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity Type'),
      '#default_value' => $this->options['entity_type'],
    ];

    $form['entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#default_value' => $this->options['entity_id'],
    ];

    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Name'),
      '#default_value' => $this->options['field_name'],
    ];

  }

  /**
   * {inheritdoc}
   */
  public function showExposeButton(&$form, FormStateInterface $form_state) {
    $form['expose_button'] = [
      '#prefix' => '<div class="views-expose clearfix">',
      '#suffix' => '</div>',
      // Should always come first
      '#weight' => -1000,
    ];

    // Add a checkbox for JS users, which will have behavior attached to it
    // so it can replace the button.
    $form['expose_button']['checkbox'] = [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => ['js-only']],
    ];
    $form['expose_button']['checkbox']['checkbox'] = [
      '#title' => $this->t('Expose this sort to visitors, to allow them to change it'),
      '#type' => 'checkbox',
    ];

    // Then add the button itself.
    if (empty($this->options['exposed'])) {
      $form['expose_button']['markup'] = [
        '#markup' => '<div class="description exposed-description" style="float: left; margin-right:10px">' . $this->t('This sort is not exposed. Expose it to allow the users to change it.') . '</div>',
      ];
      $form['expose_button']['button'] = [
        '#limit_validation_errors' => [],
        '#type' => 'submit',
        '#value' => $this->t('Expose sort'),
        '#submit' => [[$this, 'displayExposedForm']],
      ];
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 0;
    }
    else {
      $form['expose_button']['markup'] = [
        '#markup' => '<div class="description exposed-description">' . $this->t('This sort is exposed. If you hide it, users will not be able to change it.') . '</div>',
      ];
      $form['expose_button']['button'] = [
        '#limit_validation_errors' => [],
        '#type' => 'submit',
        '#value' => $this->t('Hide sort'),
        '#submit' => [[$this, 'displayExposedForm']],
      ];
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 1;
    }
  }

}
