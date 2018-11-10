<?php

namespace Drupal\cn_feeds\Feeds\Processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\Processor\EntityProcessorBase as Base;
use Drupal\file\FileInterface;
use Goutte\Client;
use GuzzleHttp\Client as HttpClient;

abstract class EntityProcessorBase extends Base {

  /**
   * Microsoft.
   */
  const FEED_ID_MICROSOFT = 16;

  /**
   * arXiv.
   */
  const FEED_ID_ARXIV = 9;

  /**
   * arXiv feeds.
   */
  const FEED_IDS_ARXIV = [
    9,
    20,
  ];

  /**
   * Article.
   */
  const TYPE_ARTICLE = 210;

  /**
   * Whitepapers.
   */
  const TYPE_WHITEPAPER = 211;

  /**
   * Maximum request time in seconds.
   */
  const HTTP_REQUEST_TIMEOUT = 1800;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  public $mediaStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  public $nodeStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  public $paragraphStorage;

  /**
   * @var \Goutte\Client
   */
  public $crawler;

  /**
   * @var \GuzzleHttp\Client
   */
  public $httpClient;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  public $logger;

  /**
   * Constructs an EntityProcessorBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $query_factory, $entity_type_bundle_info);

    // Entity storage.
    $this->mediaStorage = $this->entityTypeManager->getStorage('media');
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
    $this->paragraphStorage = $this->entityTypeManager->getStorage('paragraph');

    // Crawler.
    $this->crawler = new Client();
    $this->httpClient = new HttpClient([
      'connect_timeout' => static::HTTP_REQUEST_TIMEOUT,
      'timeout' => static::HTTP_REQUEST_TIMEOUT,
      'read_timeout' => static::HTTP_REQUEST_TIMEOUT,
    ]);

    // Logger.
    $this->logger = \Drupal::logger('cn_feeds');
  }

  /**
   * Execute mapping on an item.
   *
   * This method encapsulates the central mapping functionality. When an item is
   * processed, it is passed through map() where the properties of $source_item
   * are mapped onto $target_item following the processor's mapping
   * configuration.
   */
  protected function map(FeedInterface $feed, EntityInterface $entity, ItemInterface $item) {
    $mappings = $this->feedType->getMappings();

    // Mappers add to existing fields rather than replacing them. Hence we need
    // to clear target elements of each item before mapping in case we are
    // mapping on a prepopulated item such as an existing node.
    foreach ($mappings as $mapping) {
      unset($entity->{$mapping['target']});
    }

    // Gather all of the values for this item.
    $source_values = [];
    foreach ($mappings as $mapping) {
      $target = $mapping['target'];

      foreach ($mapping['map'] as $column => $source) {

        if (!isset($source_values[$target][$column])) {
          $source_values[$target][$column] = [];
        }

        $value = $item->get($source);
        if (!is_array($value)) {
          $source_values[$target][$column][] = $value;
        }
        else {
          $source_values[$target][$column] = array_merge($source_values[$target][$column], $value);
        }
      }
    }

    // Rearrange values into Drupal's field structure.
    $field_values = [];
    foreach ($source_values as $field => $field_value) {
      $field_values[$field] = [];
      foreach ($field_value as $column => $values) {
        // Use array_values() here to keep our $delta clean.
        foreach (array_values($values) as $delta => $value) {
          $field_values[$field][$delta][$column] = $value;
        }
      }
    }

    // Set target values.
    foreach ($mappings as $delta => $mapping) {
      $plugin = $this->feedType->getTargetPlugin($delta);
      $plugin->setTarget($feed, $entity, $mapping['target'], $field_values[$mapping['target']]);
    }

    return $entity;
  }

  /**
   * @param $method
   * @param $url
   * @return \Symfony\Component\DomCrawler\Crawler
   */
  public function request($method, $url) {
    try {
      ini_set('max_execution_time', static::HTTP_REQUEST_TIMEOUT);
      set_time_limit (static::HTTP_REQUEST_TIMEOUT);
      $response = $this->httpClient->request($method, $url);
      return $response;
    }
    catch (\Exception $e) {
      $this->logger->alert('HTTP request failed.');
    }
  }

  /**
   * @param $method
   * @param $url
   * @return \Symfony\Component\DomCrawler\Crawler
   */
  public function crawl($method, $url) {
    try {
      $response = $this->crawler->request($method, $url);
      return $response;
    }
    catch (\Exception $e) {
      $this->logger->alert('HTTP crawler request failed.');
    }
  }

  /**
   * Maps a source value to the image field and appends values as needed.
   *
   * @param $entity
   * @param $source_value
   */
  public function processMediaField(ContentEntityInterface $entity, $source_value, $media_bundle, $field_name, $media_field_name, $file_extension = NULL) {
    $field_values = $entity->get($field_name)->getValue();
    if (!empty($source_value)) {

      // $field_definition = $entity->getFieldDefinition($field_name);
      // $field_settings = $field_definition->getSettings();
      // $target_directory = $field_definition->doGetUploadLocation($field_settings);

      $target_directory = NULL;

      $file_entity = $this->downloadFile($source_value, $target_directory, $file_extension);
      if (!empty($file_entity)) {
        if (!empty($field_values)) {
          $media_entity = $this->mediaStorage->load($field_values[0]['target_id']);
          $media_entity->set('name', $file_entity->getFileName());
          $media_entity->{$media_field_name}->target_id = $file_entity->id();
          $media_entity->save();
        }
        else {
          $media_entity = $this->createMediaFromFile($file_entity, $media_bundle, $media_field_name);
          // Relate media entity to host entity.
          $entity->{$field_name}->target_id = $media_entity->id();
        }
      }
    }
  }

  /**
   * Downloads a file given a URL and stores as file entity.
   *
   * @param string $source_value
   * @param string $target_directory
   *
   * @return bool|\Drupal\file\FileInterface|false
   */
  public function downloadFile($source_value, $target_directory = NULL, $file_extension = NULL) {

    // Make HTTP request for file URL.
    $response = $this->request('GET', $source_value);
    if (!$response instanceof \GuzzleHttp\Psr7\Response) {
      $this->logger->alert('Failed to download file.');
      return FALSE;
    }

    $file_contents = (string) $response->getBody();

    if (!empty($file_contents)) {

      // Get MIME type from response.
      $mime_type = $response->getHeader('Content-Type');
      if (!empty($mime_type)) {
        $mime_type = $mime_type[0];
      }
      else {
        $mime_type = 'application/octet-stream';
      }

      // Get filename from URL.
      $path_parts = pathinfo($source_value);
      $filename = $path_parts['basename'];

      if (!empty($file_extension)) {
        $filename = "{$filename}.{$file_extension}";
      }

      if (empty($target_directory)) {
        $date_path = date("Y").'/'.date("m").'/'.date("d").'/'.date('H').'/';
        $target_directory = "public://feeds/{$date_path}";
      }

      if (!file_exists($target_directory)) {
        @drupal_mkdir($target_directory, NULL, TRUE);
      }

      $target_file_path = "{$target_directory}/{$filename}";

      // Save file.
      $file_entity = file_save_data($file_contents, $target_file_path, FILE_EXISTS_RENAME);

      if ($file_entity instanceof FileInterface) {
        $file_entity->setFilename($filename);
        $file_entity->setPermanent(TRUE);
        $file_entity->setMimeType($mime_type);
        $file_entity->save();
      }

      return $file_entity;
    }

    return FALSE;
  }

  /**
   * Creates a media entity given a file entity.
   *
   * @param \Drupal\file\FileInterface $file_entity
   * @param string $media_bundle
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function createMediaFromFile(FileInterface $file_entity, $media_bundle, $media_field_name) {
    $media_entity = $this->mediaStorage->create([
      'bundle' => $media_bundle,
      'name' => $file_entity->getFileName(),
    ]);
    $media_entity->{$media_field_name}->target_id = $file_entity->id();
    $media_entity->save();

    // Relate media entity to host entity.
    return $media_entity;
  }

  /**
   * @param $markup
   * @return mixed
   */
  public function cleanMarkup($markup = NULL, $strip_tags = FALSE) {

    if (is_string($markup)) {
      $markup = str_replace('<br />', ' ', $markup);
      $markup = str_replace(array("\r", "\n"), ' ', $markup);
      $markup = html_entity_decode($markup);
    }

    if ($strip_tags == TRUE) {
      $markup = strip_tags($markup);
    }

    return $markup;
  }

}
