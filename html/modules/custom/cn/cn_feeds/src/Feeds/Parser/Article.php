<?php

namespace Drupal\cn_feeds\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\SyndicationItem;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Zend\Feed\Reader\Exception\ExceptionInterface;
use Zend\Feed\Reader\Reader;
use Drupal\feeds\Feeds\Parser\SyndicationParser;
use Drupal\cn_feeds\Feeds\Reader\ArticleReader;

/**
 * Defines an RSS and Atom feed parser.
 *
 * @FeedsParser(
 *   id = "article",
 *   title = @Translation("Article Parser"),
 *   description = @Translation("Extends RSS/Atom to provide article parser.")
 * )
 */
class Article extends SyndicationParser implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $result = new ParserResult();
    Reader::setExtensionManager(\Drupal::service('feed.bridge.reader'));
    Reader::registerExtension('GeoRSS');

    $raw = $fetcher_result->getRaw();
    if (!strlen(trim($raw))) {
      throw new EmptyFeedException();
    }

    try {
      $channel = ArticleReader::importString($raw);
    }
    catch (ExceptionInterface $e) {
      $args = ['%site' => $feed->label(), '%error' => trim($e->getMessage())];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }

    foreach ($channel as $delta => $entry) {

      $xml_raw = $entry->saveXml();
      $xml_element = new \SimpleXMLElement($xml_raw);

      $media_children = $xml_element->children('media', True);
      $media_content = NULL;
      $image_url = NULL;
      if (!empty($media_children)) {
        if ($media_content = $media_children->content->attributes()['url']) {
          $image_url = $media_content->__toString();
        }
      }

      $item = new SyndicationItem();
      // Move the values to an array as expected by processors.
      $item
        ->set('title', $entry->getTitle())
        ->set('guid', $entry->getId())
        ->set('url', $entry->getLink())
        ->set('guid', $entry->getId())
        ->set('url', $entry->getLink())
        ->set('media_content', $image_url)
        ->set('description', $entry->getDescription())
        ->set('content', $entry->getContent())
        ->set('tags', $entry->getCategories()->getValues())
        ->set('feed_title', $channel->getTitle())
        ->set('feed_description', $channel->getDescription())
        ->set('feed_url', $channel->getLink());

      if ($image = $channel->getImage()) {
        $item->set('feed_image_uri', $image['uri']);
      }

      if ($enclosure = $entry->getEnclosure()) {
        $item->set('enclosures', [rawurldecode($enclosure->url)]);
      }

      if ($author = $entry->getAuthor()) {
        $author += ['name' => '', 'email' => ''];
        $item->set('author_name', $author['name'])
          ->set('author_email', $author['email']);
      }
      if ($date = $entry->getDateModified()) {
        $item->set('timestamp', $date->getTimestamp());
      }

      if ($point = $entry->getGeoPoint()) {
        $item->set('georss_lat', $point['lat'])
          ->set('georss_lon', $point['lon']);
      }

      $result->addItem($item);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    $mapping = parent::getMappingSources();
    $mapping['media_content'] = [
      'label' => $this->t('Media Content'),
      'description' => $this->t('The media URL.'),
    ];
    $mapping['media_description'] = [
      'label' => $this->t('Media Description'),
      'description' => $this->t('The media description.'),
    ];
    $mapping['content_encoded'] = [
      'label' => $this->t('Content Encoded'),
      'description' => $this->t('The full body.'),
    ];
    return $mapping;
  }

}
