<?php

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Implements hook_theme().
 */
function cn_core_theme() {
  return [
    'ajax_confirm__anon_post' => [
      'variables' => [
        'heading' => '',
        'body' => '',
        'modal' => TRUE,
        'id' => NULL,
      ],
    ],
    'episode_recent' => [
      'variables' => [],
    ],
    'block__newsletter_signup' => [
      'variables' => [],
    ],
    'ajax_confirm' => [
      'variables' => [
        'id' => 'ajax--confirm',
        'content' => NULL,
        'heading' => NULL,
        'icon' => NULL,
        'size' => 'md',
      ],
    ],
    'ajax_confirm__webform' => [
      'variables' => [],
    ],
    'form__enhanced' => [
      'base hook' => 'form',
    ],
    'form__plain' => [
      'base hook' => 'form',
    ],
    'entity_form__modal' => [
      'render element' => 'form',
    ],
    'entity_form' => [
      'render element' => 'form',
    ],
    'form__multi_column' => [
      'render element' => 'form',
    ],
    'juicy_ace_editor' => [
      'variables' => [
        'code' => NULL,
      ],
    ],
    'art19_player' => [
      'variables' => [
        'guid' => NULL,
      ],
    ],
  ];
}

/**
 * Implements hook_preprocess_HOOK() for HTML document templates.
 *
 * Adds body classes if certain regions have content.
 */
function cn_core_preprocess_html(&$variables) {

  /** @var \Drupal\Core\Routing\CurrentRouteMatch $route_match */
  $route_match = \Drupal::routeMatch();

  /** @var \Symfony\Component\Routing\Route $route */
  $route = $route_match->getRouteObject();

  if ($route->hasOption('_entity_form_route')) {
    $variables['attributes']['class'][] = 'entity-form-route';
  }

  if ($route->hasOption('_entity_display_route')) {
    $variables['attributes']['class'][] = 'entity-display-route';
  }

}

/**
 * Implements hook_preprocess_HOOK() for HTML document templates.
 *
 * Adds body classes if certain regions have content.
 */
function cn_core_preprocess_page(&$variables) {
}

/**
 * Implements hook_preprocess_HOOK() for entity forms.
 */
function cn_core_preprocess_entity_form(&$variables) {
  if (!empty($variables['form']['#modal'])) {
    $variables['hide_advanced'] = TRUE;
  }

  if ($variables['form']['#op'] == 'anonymous'){
    $variables['hide_advanced'] = TRUE;
  }
}

/**
 * Implements hook_preprocess_field().
 */
function cn_core_preprocess_field(&$variables) {

  if (count($variables['items']) < 2) {
    // No need for delimiter.
    return;
  }

  // Get a few convenient handles.
  $entity      = $variables['element']['#object'];
  $field_name  = $variables['element']['#field_name'];
  $view_mode   = $variables['element']['#view_mode'];

  // Drill down to field formatter settings.
  $render_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
  $field_display = $render_display->getComponent($field_name);

  if (empty($field_display['third_party_settings']['cn_core'])) {
    return;
  }

  if (!empty($delimiter = $field_display['third_party_settings']['cn_core']['delimiter'])) {

    // Sanitize the delimiter, allow a few reasonable HTML elements.
    $safe_delimiter = Xss::filter($delimiter, array('br', 'hr', 'span', 'img', 'wbr'));

    // Modify the output if necessary.
    if (!empty($safe_delimiter)) {
      $item_keys = array_keys($variables['items']);
      $last_item_delta = end($item_keys);

      foreach ($variables['items'] as $delta => &$item) {
        if ($delta != $last_item_delta) {
          $item['content']['#suffix'] = $safe_delimiter;
        }
      }
    }
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function cn_core_preprocess_block__newsletter_signup(&$variables) {
  $message = \Drupal::entityTypeManager()
    ->getStorage('contact_message')
    ->create([
      'contact_form' => 'newsletter_signup',
    ]);
  $form = \Drupal::service('entity.form_builder')->getForm($message);
  $variables['form'] = $form;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function cn_core_preprocess_file_link(&$variables) {

  $attributes_array = $variables['attributes']->toArray();

  if (empty($attributes_array['data-button'])) {
    return;
  }

  $file = $variables['file'];
  $options = [];

  $file_entity = ($file instanceof File) ? $file : File::load($file->fid);
  // @todo Wrap in file_url_transform_relative(). This is currently
  // impossible. As a work-around, we currently add the 'url.site' cache context
  // to ensure different file URLs are generated for different sites in a
  // multisite setup, including HTTP and HTTPS versions of the same site.
  // Fix in https://www.drupal.org/node/2646744.
  $url = file_create_url($file_entity->getFileUri());
  $variables['#cache']['contexts'][] = 'url.site';

  $mime_type = $file->getMimeType();
  // Set options as per anchor format described at
  // http://microformats.org/wiki/file-format-examples
  $options['attributes']['type'] = $mime_type . '; length=' . $file->getSize();

  // Use the description as the link text if available.
  if (empty($variables['description'])) {
    $link_text = $file_entity->getFilename();
  }
  else {
    $link_text = $variables['description'];
    $options['attributes']['title'] = $file_entity->getFilename();
  }

  // Classes to add to the file field for icons.
  $classes = [
    'file',
    // Add a specific class for each and every mime type.
    'file--mime-' . strtr($mime_type, ['/' => '-', '.' => '-']),
    // Add a more general class for groups of well known MIME types.
    'file--' . file_icon_class($mime_type),
    'btn',
    'btn-primary',
    'btn-lg',
  ];

  // Set file classes to the options array.
  $variables['attributes'] = new Attribute($variables['attributes']);
  $variables['attributes']->addClass($classes);

  $variables['link'] = \Drupal::l('Download PDF', Url::fromUri($url, $options));
}

/**
 * Provides alternate named suggestions for a specific theme hook.
 *
 * @param array $variables
 *   An array of variables passed to the theme hook.
 * @param string $hook
 *   The base hook name.
 *
 * @return array
 *   An array of theme suggestions.
 */
function cn_core_theme_suggestions_webform(array $variables, $hook = NULL) {
  $suggestions = [];
  $suggestions[] = 'webform__enhanced';
  return $suggestions;
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function cn_core_theme_suggestions_slick_thumbnail_alter(array &$suggestions, array $variables) {

  $view = $variables['element']['#settings']['view_name'];
  $optionset = $variables['element']['#settings']['optionset'];

  if (!empty($variables['element']['#settings']['view_name'])) {
    $suggestions[] = "slick_thumbnail__view__{$view}__{$optionset}";
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function cn_core_theme_suggestions_slick_alter(array &$suggestions, array $variables) {

  $view = $variables['element']['#settings']['view_name'];
  $optionset = $variables['element']['#settings']['optionset'];

  if (!empty($variables['element']['#settings']['view_name'])) {
    $suggestions[] = "slick__view__{$view}__{$optionset}";
  }
}
