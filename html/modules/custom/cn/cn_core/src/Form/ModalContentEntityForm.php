<?php

namespace Drupal\cn_core\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

/**
 * Form handler for the node edit forms.
 */
class ModalContentEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if (!empty($form['field_listing_type'])) {
      $form['field_listing_type']['widget']['#after_build'][] = [$this, 'entityFormListingAfterBuild'];
    }

    $form['#modal'] = TRUE;

    return $form;
  }

  /**
   * @param array $element
   * @param FormStateInterface $form_state
   * @param array $complete_form
   * @return array
   */
  public function processEntityForm(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Remove advanced sidebar in modal.
    $element['advanced']['#access'] = FALSE;

    if (!empty($element['status'])) {
      $element['advanced']['status'] = $element['status'];
      $element['advanced']['status']['#weight'] = -990;
      $element['advanced']['status']['#group'] = 'advanced';
      unset($element['status']);
    }

    return $element;
  }

  /**
   * @param array $element
   * @param FormStateInterface $form_state
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function entityFormListingAfterBuild(array $element, FormStateInterface $form_state) {

    foreach ($element as $key => $child) {
      if (is_numeric($key) && is_array($child) && ($child['#type'] == 'radio')) {

        $term_id = $key;

        $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term_id);

        if (!empty($term)) {
          $field_values_description = $term->get('description')->getValue();
          if (!empty($field_values_description)) {
            $suffix = '<div class="field--suffix">';
            $suffix .= check_markup($field_values_description[0]['value'], $field_values_description[0]['format']);

            $field_values_price = $term->get('field_price')->getValue();
            if (!empty($field_values_price)) {
              $suffix .= '<div class="listing-type-price">Cost = <strong>$' . $field_values_price[0]['value'] . '</strong></div>';
            }

            $suffix .= '</div></div>';
            $element[$key]['#suffix'] = $suffix;
            $element[$key]['#prefix'] = '<div class="listing-item-wrapper">';
          }
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    $element['submit']['#value'] = t('Submit for Approval');

    $element['close'] = [
      '#type' => 'button',
      '#limit_validation_errors' => [],
      '#executes_submit_callback' => FALSE,
      '#access' => FALSE,
      '#value' => $this->t('Close Window'),
      '#weight' => -110,
      '#ajax' => [
        'callback' => '::ajaxOpClose',
        'wrapper' => $form['#ajax_wrapper'],
      ],
      '#attributes' => [
        'class' => [
          'btn',
          'btn-lg',
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    if ($this->entity->isNew() && $this->entity->hasField('field_anon')) {
      $this->entity->field_anon->value = TRUE;
    }

    parent::save($form, $form_state);
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @param AjaxResponse $response
   * @return array|AjaxResponse
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function ajaxOpSubmit(array &$form, FormStateInterface $form_state, Request $request, AjaxResponse $response = NULL) {
    if ($form_state->getErrors()) {
      return parent::ajaxOpSubmit($form, $form_state, $request);
    }

    // Remove any status messages.
    $messages = drupal_get_messages();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entity;

    $response = new AjaxResponse();

    if ($entity->bundle() == 'job') {
      $heading = 'Thank you for submitting a job with Talking Machines';
    }
    elseif ($entity->bundle() == 'event') {
      $heading = 'Thank you for submitting an event with Talking Machines';
    }
    else {
      $heading = 'Thank you for posting your submission with Talking Machines.';
    }

    $new_wrapper_id = Html::getUniqueId('ajax--wrapper');
    $form['#new_wrapper_id'] = $new_wrapper_id;

    $content = [
      '#theme' => 'ajax_confirm__anon_post',
      '#heading' => $this->t($heading),
      '#id' => $new_wrapper_id,
    ];

    $response->addCommand(new HtmlCommand('#' . $form['#ajax_wrapper'], $content));
    $response->addCommand(new InvokeCommand('.ui-dialog', 'removeClass', ['modal-lg']));
    $response->addCommand(new InvokeCommand('.ui-dialog', 'addClass', ['modal-sm']));

    return parent::ajaxOpSubmit($form, $form_state, $request, $response);
  }

}
