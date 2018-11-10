<?php

namespace Drupal\cn_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Base for handler for taxonomy term edit forms.
 *
 * @internal
 */
class TermForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $term = $this->entity;
    $vocab_storage = $this->entityManager->getStorage('taxonomy_vocabulary');
    $taxonomy_storage = $this->entityManager->getStorage('taxonomy_term');
    $vocabulary = $vocab_storage->load($term->bundle());

    $parent = array_keys($taxonomy_storage->loadParents($term->id()));
    $form_state->set(['taxonomy', 'parent'], $parent);
    $form_state->set(['taxonomy', 'vocabulary'], $vocabulary);

    $form['vid'] = [
      '#type' => 'value',
      '#value' => $vocabulary->id(),
    ];

    $form['tid'] = [
      '#type' => 'value',
      '#value' => $term->id(),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Ensure numeric values.
    if ($form_state->hasValue('weight') && !is_numeric($form_state->getValue('weight'))) {
      $form_state->setErrorByName('weight', $this->t('Weight value must be numeric.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $term = parent::buildEntity($form, $form_state);

    // Prevent leading and trailing spaces in term names.
    $term->setName(trim($term->getName()));

    // Assign parents with proper delta values starting from 0.
    $term->parent = array_keys($form_state->getValue('parent'));

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $term = $this->entity;

    $result = $term->save();

    $edit_link = $term->link($this->t('Edit'), 'edit-form');
    $view_link = $term->link($term->getName());
    switch ($result) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new term %term.', ['%term' => $view_link]));
        $this->logger('taxonomy')->notice('Created new term %term.', ['%term' => $term->getName(), 'link' => $edit_link]);
        break;
      case SAVED_UPDATED:
        drupal_set_message($this->t('Updated term %term.', ['%term' => $view_link]));
        $this->logger('taxonomy')->notice('Updated term %term.', ['%term' => $term->getName(), 'link' => $edit_link]);
        break;
    }

    $current_parent_count = count($form_state->getValue('parent'));
    $previous_parent_count = count($form_state->get(['taxonomy', 'parent']));
    // Root doesn't count if it's the only parent.
    if ($current_parent_count == 1 && $form_state->hasValue(['parent', 0])) {
      $current_parent_count = 0;
      $form_state->setValue('parent', []);
    }

    // If the number of parents has been reduced to one or none, do a check on the
    // parents of every term in the vocabulary value.
    $vocabulary = $form_state->get(['taxonomy', 'vocabulary']);
    if ($current_parent_count < $previous_parent_count && $current_parent_count < 2) {
      taxonomy_check_vocabulary_hierarchy($vocabulary, $form_state->getValues());
    }
    // If we've increased the number of parents and this is a single or flat
    // hierarchy, update the vocabulary immediately.
    elseif ($current_parent_count > $previous_parent_count && $vocabulary->getHierarchy() != VocabularyInterface::HIERARCHY_MULTIPLE) {
      $vocabulary->setHierarchy($current_parent_count == 1 ? VocabularyInterface::HIERARCHY_SINGLE : VocabularyInterface::HIERARCHY_MULTIPLE);
      $vocabulary->save();
    }

    $form_state->setValue('tid', $term->id());
    $form_state->set('tid', $term->id());
  }

}
