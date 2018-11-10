<?php

namespace Drupal\cn_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

/**
 * Form handler for the node edit forms.
 */
class NodeForm extends ContentEntityForm {

  /**
   * Processes entity form.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #available_countries or #used_fields is malformed.
   */
  public function processEntityForm(array &$element, FormStateInterface $form_state, array &$complete_form) {

    if ($this->getOperation() != 'anonymous') {
      $element['advanced']['actions'] = $element['actions'];
      $element['advanced']['actions']['#weight'] = -1000;
      unset($element['actions']);
    }
    else {
      $element['actions']['submit']['#value'] = t('Submit for Approval');
    }

    if (!empty($element['status'])) {
      $element['advanced']['status'] = $element['status'];
      $element['advanced']['status']['#weight'] = -990;
      $element['advanced']['status']['#group'] = 'advanced';
      unset($element['status']);
    }

    return $element;
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

    $new_wrapper_id = NULL;

    if ($this->getOperation() == 'anonymous') {
      // Hide messages.
      $messages = drupal_get_messages();

      $response = new AjaxResponse();
      $modal = FALSE;

      $heading = 'Thank you for posting your submission with Talking Machines.';

      $new_wrapper_id = Html::getUniqueId('ajax--wrapper');
      $form['#new_wrapper_id'] = $new_wrapper_id;

      $content = [
        '#theme' => 'ajax_confirm__anon_post',
        '#heading' => $heading,
        '#modal' => $modal,
        '#id' => $new_wrapper_id,
      ];
      $response->addCommand(new HtmlCommand('.node-article-anonymous-form.node-form', $content));
    }

    return parent::ajaxOpSubmit($form, $form_state, $request, $response);
  }

}
