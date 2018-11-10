<?php

namespace Drupal\cn_core\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\views\Plugin\Block\ViewsBlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\ViewExecutableFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Views Exposed Filter' block.
 *
 * @Block(
 *   id = "views_exposed_form",
 *   admin_label = @Translation("Views Exposed Form"),
 * )
 */
class ViewsExposedForm extends ViewsBlockBase {

  /**
   * Constructs a \Drupal\views\Plugin\Block\ViewsBlockBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The views storage.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewExecutableFactory $executable_factory, EntityStorageInterface $storage, AccountInterface $user) {
    $this->pluginId = $plugin_id;
    $delta = $this->getDerivativeId();
    list($name, $this->displayID) = explode('-', $delta, 2);
    // Load the view.
    $view = $storage->load($name);
    $this->view = $executable_factory->get($view);
    $this->displaySet = $this->view->setDisplay($this->displayID);
    $this->user = $user;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('views.executable'),
      $container->get('entity.manager')->getStorage('view'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Set the default label to '' so the views internal title is used.
    $form['label']['#default_value'] = '';
    $form['label']['#access'] = FALSE;

    // Unset the machine_name provided by BlockForm.
    unset($form['id']['#machine_name']['source']);
    // Prevent users from changing the auto-generated block machine_name.
    $form['id']['#access'] = FALSE;
    $form['#pre_render'][] = '\Drupal\views\Plugin\views\PluginBase::preRenderAddFieldsetMarkup';

    // Allow to override the label on the actual page.
    $form['views_label_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override title'),
      '#default_value' => !empty($this->configuration['views_label']),
    ];

    $form['views_label_fieldset'] = [
      '#type' => 'fieldset',
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[views_label_checkbox]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $form['views_label'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['views_label'] ?: $this->view->getTitle(),
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[views_label_checkbox]"]' => ['checked' => TRUE],
          ],
        ],
      ],
      '#fieldset' => 'views_label_fieldset',
    ];

    if ($this->view->storage->access('edit') && \Drupal::moduleHandler()->moduleExists('views_ui')) {
      $form['views_label']['#description'] = $this->t('Changing the title here means it cannot be dynamically altered anymore. (Try changing it directly in <a href=":url">@name</a>.)', [':url' => \Drupal::url('entity.view.edit_display_form', ['view' => $this->view->storage->id(), 'display_id' => $this->displayID]), '@name' => $this->view->storage->label()]);
    }
    else {
      $form['views_label']['#description'] = $this->t('Changing the title here means it cannot be dynamically altered anymore.');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = $this->view->display_handler->getCacheMetadata()->getCacheContexts();
    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = $this->view->display_handler->viewExposedFormBlocks();

    // Before returning the block output, convert it to a renderable array with
    // contextual links.
    $this->addContextualLinks($output, 'exposed_filter');

    return $output;
  }

}
