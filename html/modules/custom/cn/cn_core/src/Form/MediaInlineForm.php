<?php

namespace Drupal\cn_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Media inline form handler.
 */
class MediaInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_form['#entity'];
    $entity_form = parent::entityForm($entity_form, $form_state);

    $entity_form['name']['#access'] = FALSE;

    $form['#process'][] = '::processMediaForm';

    if (!empty($entity_form['revision_log_message'])) {
      $entity_form['revision_log_message']['#access'] = FALSE;
    }

    return $entity_form;
  }

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
  public static function processMediaForm(array &$element, FormStateInterface $form_state, array &$complete_form) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormValidate(array &$entity_form, FormStateInterface $form_state) {
    // Perform entity validation only if the inline form was submitted,
    // skipping other requests such as file uploads.
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#ief_submit_trigger'])) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $entity_form['#entity'];
      $this->buildEntity($entity_form, $entity, $form_state);
      $form_display = $this->getFormDisplay($entity, $entity_form['#form_mode']);
      $form_display->validateFormValues($entity, $entity_form, $form_state);
      $entity->setValidationRequired(FALSE);

      foreach($form_state->getErrors() as $name => $message) {
        // $name may be unknown in $form_state and
        // $form_state->setErrorByName($name, $message) may suppress the error message.
        $form_state->setError($triggering_element, $message);
      }
    }
  }

}
