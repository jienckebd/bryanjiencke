<?php

namespace Drupal\bd_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form controller for contact message forms.
 *
 * @internal
 */
class MessageForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $message = $this->entity;
    $form = parent::form($form, $form_state, $message);
    unset($form['#theme']);
    $form['#attributes']['class'][] = 'contact-form';

    if (!empty($message->preview)) {
      $form['preview'] = [
        '#theme_wrappers' => ['container__preview'],
        '#attributes' => ['class' => ['preview']],
      ];
      $form['preview']['message'] = $this->entityManager->getViewBuilder('contact_message')->view($message, 'full');
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail'),
      '#required' => TRUE,
    ];
    if ($user->isAnonymous()) {
      $form['#attached']['library'][] = 'core/drupal.form';
      $form['#attributes']['data-user-info-from-browser'] = TRUE;
    }
    // Do not allow authenticated users to alter the name or email values to
    // prevent the impersonation of other users.
    else {
      $form['name']['#type'] = 'item';
      $form['name']['#value'] = $user->getDisplayName();
      $form['name']['#required'] = FALSE;
      $form['name']['#plain_text'] = $user->getDisplayName();
    }

    // The user contact form has a preset recipient.
    if ($message->isPersonal()) {
      $form['recipient'] = [
        '#type' => 'item',
        '#title' => $this->t('To'),
        '#value' => $message->getPersonalRecipient()->id(),
        'name' => [
          '#theme' => 'username',
          '#account' => $message->getPersonalRecipient(),
        ],
      ];
    }

    $form['name']['#title'] = t('Name');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $elements = parent::actions($form, $form_state);

    $contact_form = $this->entity->getContactForm();
    $settings = $contact_form->getThirdPartySettings('contact_storage');

    $elements['submit']['#value'] = !empty($settings['submit_text']) ? $settings['submit_text'] : $this->t('Send Message');
    return $elements;
  }

  /**
   * Form submission handler for the 'preview' action.
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $message = $this->entity;
    $message->preview = TRUE;
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $message = parent::validateForm($form, $form_state);

    // Check if flood control has been activated for sending emails.
    if (!$this->currentUser()->hasPermission('administer contact forms') && (!$message->isPersonal() || !$this->currentUser()->hasPermission('administer users'))) {
      $limit = $this->config('contact.settings')->get('flood.limit');
      $interval = $this->config('contact.settings')->get('flood.interval');

      if (!$this->flood->isAllowed('contact', $limit, $interval)) {
        $form_state->setErrorByName('', $this->t('You cannot send more than %limit messages in @interval. Try again later.', [
          '%limit' => $limit,
          '@interval' => $this->time->formatInterval($interval),
        ]));
      }
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $message = $this->entity;
    $user = $this->currentUser();
    // Save the message. In core this is a no-op but should contrib wish to
    // implement message storage, this will make the task of swapping in a real
    // storage controller straight-forward.
    $message->save();
    \Drupal::service('contact.mail_handler')->sendMailMessages($message, $user);
    $contact_form = $message->getContactForm();

    $this->flood->register('contact', $this->config('contact.settings')->get('flood.interval'));
    if ($submission_message = $contact_form->getMessage()) {
      drupal_set_message($submission_message);
    }

    // To avoid false error messages caused by flood control, redirect away from
    // the contact form; either to the contacted user account or the front page.
    if ($message->isPersonal() && $user->hasPermission('access user profiles')) {
      $form_state->setRedirectUrl($message->getPersonalRecipient()->urlInfo());
    }
    else {
      $form_state->setRedirectUrl($contact_form->getRedirectUrl());
    }
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

    // Something is overriding the
    $email = $form_state->getValue('mail');

    $response = new AjaxResponse();

    $new_wrapper_id = Html::getUniqueId('ajax--wrapper');
    $form['#new_wrapper_id'] = $new_wrapper_id;

    if ($form['#form_id'] == 'contact_message_sitewide_form') {
      $system_site_config = $this->configFactory->get('system.site');
      $sys_email = $system_site_config->get('mail');
      $content = [
        '#theme' => 'ajax_confirm',
        '#heading' => t('Thanks for reaching out!'),
        '#icon' => 'check',
        '#content' => t('Our team will review your message within 3 business days. If you need immediate assistance, please contact us at @sys_email.', [
          '@sys_email' => $sys_email,
        ]),
        '#id' => $new_wrapper_id,
      ];
    }
    elseif ($form['#form_id'] == 'contact_message_newsletter_signup_form') {
      if ($_ENV['SYS_CONTEXT'] != 'local') {
        mailchimp_subscribe('9ecb0e9f49', $email);
      }
      $content = [
        '#theme' => 'ajax_confirm',
        '#heading' => 'Sign up complete',
        '#icon' => 'check',
        '#content' => t('Thanks for signing up! Your e-mail address @email has been added to our newsletter.', [
          '@email' => $email,
        ]),
        '#id' => $new_wrapper_id,
      ];
    }
    else {
      $content = [
        '#theme' => 'ajax_confirm',
        '#heading' => 'Sign up complete',
        '#icon' => 'check',
        '#content' => t('Thanks for signing up! Your e-mail address @email has been added to our list.', [
          '@email' => $email,
        ]),
        '#id' => $new_wrapper_id,
      ];
    }

    $wrapper = $form['#ajax_wrapper'];

    $response->addCommand(new HtmlCommand('#' . $wrapper, $content));
    return parent::ajaxOpSubmit($form, $form_state, $request, $response);
  }

}
