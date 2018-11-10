<?php

namespace Drupal\cn_core\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * A query parameter argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "entity_values",
 *   title = @Translation("Entity Values")
 * )
 */
class EntityValues extends ArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['field_name'] = ['default' => ''];
    $options['value_field'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Name'),
      '#description' => $this->t('The field name.'),
      '#default_value' => $this->options['field_name'],
    ];
    $form['value_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value Field'),
      '#description' => $this->t('The value field.'),
      '#default_value' => $this->options['value_field'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {

    $field_name = $this->options['field_name'];
    $value_field = $this->options['value_field'];
    $return = [];

    $entity = \Drupal::routeMatch()->getParameter('node');
    if ($entity->hasField($field_name)) {
      $field_values = $entity->get($field_name)->getValue();
      foreach ($field_values as $key => $data) {
        if (!empty($data[$value_field])) {
          $return[] = $data[$value_field];
        }
      }
    }

    return implode(',', $return);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

}
