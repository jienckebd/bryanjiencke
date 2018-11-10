<?php

namespace Drupal\cn_feeds\Feeds\Processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\StateInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Defines a node processor.
 *
 * Creates nodes from feed items.
 *
 * @FeedsProcessor(
 *   id = "article",
 *   title = @Translation("Article"),
 *   description = @Translation("Creates nodes from feed items."),
 *   entity_type = "node",
 *   arguments = {"@entity_type.manager", "@entity.query", "@entity_type.bundle.info"},
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 * )
 */
class ArticleProcessor extends EntityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function entityLabel() {
    return $this->t('Article');
  }

  /**
   * {@inheritdoc}
   */
  public function entityLabelPlural() {
    return $this->t('Articles');
  }

  /**
   * {@inheritdoc}
   */
  public function process(FeedInterface $feed, ItemInterface $item, StateInterface $state) {
    $existing_entity_id = $this->existingEntityId($feed, $item);
    $skip_existing = $this->configuration['update_existing'] == static::SKIP_EXISTING;

    // Bulk load existing entities to save on db queries.
    if ($skip_existing && $existing_entity_id) {
      return;
    }

    // Delay building a new entity until necessary.
    if ($existing_entity_id) {
      $entity = $this->storageController->load($existing_entity_id);
    }

    $hash = $this->hash($item);
    $changed = $existing_entity_id && ($hash !== $entity->get('feeds_item')->hash);

    // Do not proceed if the item exists, has not changed, and we're not
    // forcing the update.
    if ($existing_entity_id && !$changed && !$this->configuration['skip_hash_check']) {
      return;
    }

    // Build a new entity.
    if (!$existing_entity_id) {
      $entity = $this->newEntity($feed);
    }

    try {

      // Set field values.
      $this->map($feed, $entity, $item);

      // @todo set multiple authors using feeds instead of code.
      if ($entity->hasField('field_author')) {
        $author_values = $item->get('author_name');
        if (!empty($author_values)) {

          // Get first author.
          $author_values = strip_tags($author_values);
          $author_values = explode(',', $author_values);
          if (!empty($author_values)) {
            $author_field_values = [];
            foreach ($author_values as $key => $author_full_name) {

              $author_full_name = trim($author_full_name);

              $author_entity = $this->paragraphStorage->create([
                'type' => 'author',
              ]);
              $author_entity->field_name->value = html_entity_decode($author_full_name);
              $author_entity->save();

              $author_field_values[] = [
                'target_id' => $author_entity->id(),
                'target_revision_id' => $author_entity->revision_id->value,
              ];
            }

            // Map paragraph author entities to base entity.
            $entity->set('field_author', $author_field_values);
          }
        }
      }

      // Map content to field_content of created paragraph entity.
      if ($entity->hasField('field_content')) {
        $field_value = $entity->get('field_content')->getValue();
        if (!empty($field_value[0])) {
          $source_value = $item->get('content');
          if (!empty($source_value)) {
            $related_entity = $field_value[0]['entity'];
            $related_entity->field_content->value = $this->cleanMarkup($source_value);
            $related_entity->save();
          }
        }
      }

      // Grab remote image and map to media entity image field.
      if ($entity->hasField('field_image')) {
        $source_url = $item->get('media_content');
        $this->processMediaField($entity, $source_url, 'image', 'field_image', 'field_media_image');
      }

      // Strip tags from summary.
      if ($entity->hasField('field_summary') && !empty($entity->field_summary->value)) {
        $entity->field_summary->value = $this->cleanMarkup($entity->field_summary->value, TRUE);
      }

      // Turn social share on.

      // Promote.
      $entity->setPromoted(FALSE);
      $entity->setPublished(FALSE);

      // Check if feed is Microsoft.
      if ($feed->id() == self::FEED_ID_MICROSOFT) {
        $this->processMicrosoft($entity, $feed, $item, $state);
      }

      if (in_array($feed->id(), static::FEED_IDS_ARXIV)) {
        $this->processArxiv($entity, $feed, $item, $state);
      }

      // Set article type based on source feed. The arXiv feed maps to
      // whitepapers, and all other feeds map to articles.
      if (in_array($feed->id(), static::FEED_IDS_ARXIV)) {
        $article_type = self::TYPE_WHITEPAPER;
      }
      else {
        $article_type = self::TYPE_ARTICLE;
      }
      $entity->set('field_type', $article_type);

      // $this->entityValidate($entity);

      // This will throw an exception on failure.
      $this->entitySaveAccess($entity);

      // Set the values that we absolutely need.
      $entity->get('feeds_item')->target_id = $feed->id();
      $entity->get('feeds_item')->hash = $hash;
      $entity->get('feeds_item')->imported = REQUEST_TIME;

      // Save entity.
      $this->storageController->save($entity);

      // Track progress.
      $existing_entity_id ? $state->updated++ : $state->created++;
    }

      // Something bad happened, log it.
    catch (\Exception $e) {
      $state->failed++;
      $state->setMessage($e->getMessage(), 'warning');
    }
  }

  /**
   * Special processing for arXiv.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\feeds\FeedInterface $feed
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   * @param \Drupal\feeds\StateInterface $state
   */
  public function processArxiv(EntityInterface $entity, FeedInterface $feed, ItemInterface $item, StateInterface $state) {

    // Strip trailing text within parenthesis.
    $title = $entity->title->value;
    $title = preg_replace("/\([^)]+\)/","",$title);
    $entity->set('title', $title);

    // Get link value.
    $link_url = $item->get('url');

    /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
    $crawler = $this->crawl('GET', $link_url);

    // Grab PDF and store as file.
    $pdf_selector = '.extra-services a[accesskey="f"]';
    $pdf_href = $crawler->filter($pdf_selector)->each(function (Crawler $node, $i) {
      return $node->extract(['href']);
    });

    if (!empty($pdf_href)) {
      $pdf_href = "http://arxiv.org{$pdf_href[0][0]}";
      $this->processMediaField($entity, $pdf_href, 'document', 'field_document', 'field_document', 'pdf');
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\feeds\FeedInterface $feed
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   * @param \Drupal\feeds\StateInterface $state
   */
  public function processMicrosoft(EntityInterface $entity, FeedInterface $feed, ItemInterface $item, StateInterface $state) {
    // Get link value.
    $link_url = $item->get('url');

    /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
    $crawler = $this->crawl('GET', $link_url);

    // Crawl in to: article.post .status-publish and grab all images.
    $article_images = $crawler->filter('article.post img')->each(function (Crawler $node, $i) {
      return $node->extract(['src']);
    });

    if (!empty($article_images)) {
      foreach ($article_images as $key => $url) {
        if (!empty($url[0])) {
          $this->processMediaField($entity, $url[0], 'image', 'field_image', 'image');
          break;
        }
      }
    }
  }

}
