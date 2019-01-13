<?php

namespace Drupal\bd_core\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

/**
 * Form handler for modal forms.
 */
class ContentModal extends Content {

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
