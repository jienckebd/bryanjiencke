<?php

namespace Drupal\bd_core\Entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm as Base;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\MainContent\AjaxRenderer;
use Drupal\bd_core\Ajax\FormSubmitPassCommand;
use Drupal\bd_core\Ajax\FormSubmitFailCommand;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Form handler for the node edit forms.
 */
class Content extends Base {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Render\MainContent\AjaxRenderer
   */
  protected $ajaxRenderer;

  /**
   * @var string
   */
  public $entityTypeId;

  /**
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  public $entityType;

  /**
   * @var \Drupal\Core\Config\Entity\ConfigEntityType
   */
  public $bundleEntityType;

  /**
   * @var string
   */
  public $entityTypeLabel;

  /**
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  public $bundle;

  /**
   * @var bool
   */
  public $isNew;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Flood\FloodInterface
   *   The flood control service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Render\MainContent\AjaxRenderer
   *   The ajax renderer.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, PrivateTempStoreFactory $temp_store_factory, TimeInterface $time, FloodInterface $flood, RouteMatchInterface $route_match, AjaxRenderer $ajax_renderer) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->tempStoreFactory = $temp_store_factory;
    $this->flood = $flood;
    $this->routeMatch = $route_match;
    $this->ajaxRenderer = $ajax_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('user.private_tempstore'),
      $container->get('datetime.time'),
      $container->get('flood'),
      $container->get('current_route_match'),
      $container->get('main_content_renderer.ajax')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $return = parent::setEntity($entity);
    try {
      $this->setEntityTypeInfo();
    }
    catch (\Exception $e) {
      $this->logger('bd_core')->alert('Unable to set entity type info.');
    }
    return $return;
  }

  /**
   * Set the entity type info once the entity is set on the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setEntityTypeInfo() {
    $this->entityTypeId = $this->entity->getEntityTypeId();

    $this->entityType = $this->entityTypeManager
      ->getDefinition($this->entityTypeId);

    // If this is a bundled entity type, get the bundle info.
    if ($bundle_entity_type_id = $this->entityType->getBundleEntityType()) {
      $this->bundleEntityType = $this->entityTypeManager->getDefinition($bundle_entity_type_id);
      $this->bundle = $this->entityTypeManager
        ->getStorage($this->bundleEntityType->id())
        ->load($this->entity->bundle());
      $this->entityTypeLabel = $this->bundle->label();
    }
    else {
      $this->entityTypeLabel = $this->entityType->getLabel();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entity;

    // The isNew() method is not reliable later in form processing. So the new
    // status is stored at the beginning of the sequence.
    if (!isset($this->isNew)) {
      $this->isNew = $this->entity->isNew();
    }

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @type</em> @title', [
        '@type' => $entity->bundle(),
        '@title' => $entity->label()
      ]);
    }

    $form['#theme'] = ['entity_form'];
    $form['#attributes']['class'][] = 'entity-form';
    $form['#attached']['library'][] = 'bd_core/ajax';

    $form_id = $this->getFormId();
    $op = $this->getOperation();
    $ajax_id = "ajax--wrapper--{$form_id}--{$op}";
    $ajax_wrapper = Html::cleanCssIdentifier($ajax_id);

    $form['#ajax_wrapper'] = $ajax_wrapper;
    $form['#prefix'] = '<div id="' . $ajax_wrapper . '" class="ajax--wrapper">';
    $form['#suffix'] = '</div>';

    $form['#op'] = $op;

    $form['#process'][] = [$this, 'processEntityForm'];
    $form['#process'][] = [$this, 'afterBuildEntityForm'];

    if ($this->getOperation() != 'anonymous') {
      $form['#attributes']['class'][] = 'form--column--2';
    }

    if ($entity->hasField('changed')) {
      $form['changed'] = [
        '#type' => 'hidden',
        '#default_value' => $entity->getChangedTime(),
      ];
    }

    $form = parent::form($form, $form_state);

    $form['advanced'] = [
      '#type' => 'container',
      '#weight' => 99,
      '#attributes' => [
        'class' => [
          'entity-meta',
        ],
      ],
    ];

    $form['advanced']['#type'] = 'container';

    if (!empty($form['meta'])) {
      $form['meta']['#type'] = 'container';
      $form['meta']['#access'] = TRUE;
      $form['meta']['changed']['#wrapper_attributes']['class'][] = 'container-inline';
      $form['meta']['author']['#wrapper_attributes']['class'][] = 'container-inline';
    }

    if ($this->entityType->hasKey('revision')) {
      if (!empty($form['revision_information'])) {
        $form['revision_information']['#type'] = 'details';
        $form['revision_information']['#open'] = FALSE;
        $form['revision_information']['#group'] = 'advanced';
      }
    }
    else {
      if (!empty($form['revision_log_message'])) {
        $form['revision_log_message']['#access'] = FALSE;
      }
    }

    if (isset($form['uid'])) {
      $form['author'] = [
        '#type' => 'details',
        '#title' => t('Authoring information'),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['node-form-author'],
        ],
        '#attached' => [
          'library' => ['node/drupal.node'],
        ],
        '#weight' => 90,
        '#optional' => TRUE,
      ];

      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    if (isset($form['promote'])) {
      $form['options'] = [
        '#type' => 'details',
        '#title' => t('Promotion options'),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['node-form-options'],
        ],
        '#attached' => [
          'library' => ['node/drupal.node'],
        ],
        '#weight' => 95,
        '#optional' => TRUE,
      ];

      $form['promote']['#group'] = 'options';
    }

    if (isset($form['path'])) {
      $form['path_settings'] = [
        '#type' => 'details',
        '#title' => t('URL Alias'),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['node-form-options'],
        ],
        '#attached' => [
          'library' => ['node/drupal.node'],
        ],
        '#weight' => 100,
        '#optional' => TRUE,
      ];

      $form['path']['#group'] = 'path_settings';
    }

    return $form;

  }

  /**
   * After build for entity form.
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
  public function afterBuildEntityForm(array &$element, FormStateInterface $form_state, array &$complete_form) {
    return $element;
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
  public function processEntityForm(array &$element, FormStateInterface $form_state, array &$complete_form) {

    $element['advanced']['actions'] = $element['actions'];
    $element['advanced']['actions']['#weight'] = -1000;
    unset($element['actions']);

    if (!empty($element['status'])) {
      $element['advanced']['status'] = $element['status'];
      $element['advanced']['status']['#weight'] = -990;
      $element['advanced']['status']['#group'] = 'advanced';
      unset($element['status']);
    }

    return $element;
  }

  /**
   * Entity builder updating the node status with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\node\NodeInterface $entity
   *   The node updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\node\NodeForm::form()
   */
  public function updateStatus($entity_type_id, NodeInterface $entity, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#published_status'])) {
      $entity->setPublished($element['#published_status']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    if ($this->entity->id()) {
      $submit_text = t('Save @bundle', [
        '@bundle' => $this->entityTypeLabel,
      ]);
    }
    else {
      $submit_text = t('Create @bundle', [
        '@bundle' => $this->entityTypeLabel,
      ]);
    }

    $element['submit'] = [
      '#type' => 'submit',
      '#value' => $submit_text,
      '#ajax' => [
        'callback' => [$this, 'ajaxOpSubmit'],
        'wrapper' => $form['#ajax_wrapper'],
      ],
      '#submit' => [
        [$this, 'submitForm'],
      ],
      '#save' => [
        [$this, 'save'],
      ],
      '#attributes' => [
        'class' => [
          'btn',
          'btn-primary',
          'btn-lg',
        ],
      ],
    ];

    $element['preview'] = [
      '#type' => 'submit',
      '#value' => t('Preview'),
      '#weight' => 20,
      '#submit' => ['::submitForm', '::preview'],
      '#access' => FALSE,
    ];

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

    $form_id = $this->getFormId();
    $form_dom_id = $form['#id'];
    $new_wrapper_id = !empty($form['#new_wrapper_id']) ? $form['#new_wrapper_id'] : Html::getUniqueId('ajax--wrapper');

    // If any errors, prepare a FormSubmitFailCommand.
    if ($form_state->getErrors()) {
      $response = $this->ajaxRenderer->renderResponse($form, $request, $this->routeMatch);
      $response->addCommand(new FormSubmitFailCommand($form_id, $form_dom_id, $new_wrapper_id));
      return $response;
    }

    if (empty($response)) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->entity;
      $response = new AjaxResponse();

      // If a destination parameter is set, ajax redirect to it. Otherwise, reload
      // form and scroll to top.
      $query = $this->getRequest()->query;
      if ($query->has('destination')) {
        $redirect_target = $query->get('destination');
        $first_letter = substr($redirect_target, 0, 1);
        if ($first_letter != '/') {
          $redirect_target = "/{$redirect_target}";
        }
        $response->addCommand(new RedirectCommand($redirect_target));
      } else {

        if (!$this->isNew) {
          // If update to existing entity and no destination is set, reload the
          // form.

          $form['messages'] = [
            '#type' => 'status_messages',
            '#weight' => -1000,
          ];

          $response->addCommand(new ReplaceCommand('#' . $form['#ajax_wrapper'], $form));
          $response->addCommand(new InvokeCommand('body', 'smoothScrollToTarget', ['.dialog-off-canvas-main-canvas']));
        } else {
          $destination_url = $entity->toUrl('edit-form');
          $response->addCommand(new RedirectCommand($destination_url->toString()));
        }
      }
    }

    $response->addCommand(new FormSubmitPassCommand($form_id, $form_dom_id, $new_wrapper_id));

    return $response;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   */
  public static function ajaxOpPreview(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   */
  public static function ajaxOpClose(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->entity->hasField('changed')) {
      $this->entity->setChangedTime(REQUEST_TIME);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->save($form, $form_state);

    // Rebuild the form state values.
    $form_state->setRebuild();
    $form_state->setStorage([]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $insert = $entity->isNew();

    $context = [
      '@type' => $this->entityTypeLabel,
      '%title' => $entity->label(),
    ];

    try {
      $entity->save();

      $context_entity_type = \Drupal::routeMatch()->getParameter('context_entity_type');
      $context_entity_id = \Drupal::routeMatch()->getParameter('context_entity_id');
      if (!empty($context_entity_type) && !empty($context_entity_id)) {
        $entity_from_context = \Drupal::entityTypeManager()
          ->getStorage($context_entity_type)
          ->load($context_entity_id);

        $values = $entity_from_context->get('field_tags')->getValue();
        $values[] = [
          'target_id' => $entity->id(),
        ];
        $entity_from_context->set('field_tags', $values);
        $entity_from_context->save();
      }

      if ($insert) {
        $this->logger('content')->notice('@type: added %title.', $context);
        drupal_set_message(t('@type %title has been created.', $context));
      }
      else {
        $this->logger('content')->notice('@type: updated %title.', $context);
        drupal_set_message(t('@type %title has been updated.', $context));
      }
    }
    catch (\Exception $e) {
      $this->logger('bd_core')->error('Unable to save entity.');
      drupal_set_message($this->t('Unable to save form. Please contact support.'), 'error');
    }

  }

}
