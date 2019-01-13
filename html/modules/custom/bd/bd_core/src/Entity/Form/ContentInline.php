<?php

namespace Drupal\bd_core\Form;

use Drupal\inline_entity_form\Form\EntityInlineForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Node inline form handler.
 */
class ContentInline extends EntityInlineForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => $this->t('node'),
      'plural' => $this->t('nodes'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);
    // Remove the "Revision log" textarea,  it can't be disabled in the
    // form display and doesn't make sense in the inline form context.
    $entity_form['revision_log']['#access'] = FALSE;

    return $entity_form;
  }

}
