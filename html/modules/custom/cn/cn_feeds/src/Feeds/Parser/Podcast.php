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
use Drupal\cn_feeds\Feeds\Reader\PodcastReader;

/**
 * Defines an RSS and Atom feed parser.
 *
 * @FeedsParser(
 *   id = "podcast",
 *   title = @Translation("RSS/Atom Podcast"),
 *   description = @Translation("Extends RSS/Atom to provide podcast parser.")
 * )
 */
class Podcast extends SyndicationParser implements ParserInterface {

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
      $channel = PodcastReader::importString($raw);
    }
    catch (ExceptionInterface $e) {
      $args = ['%site' => $feed->label(), '%error' => trim($e->getMessage())];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }

    foreach ($channel as $delta => $entry) {
      $item = new SyndicationItem();
      // Move the values to an array as expected by processors.

      $keywords = $entry->getKeywords();

      if (!empty($keywords)) {
        $keywords = explode(',', $keywords);
      }

      $duration = $entry->getDuration();

      list ($hour, $minute, $second) = explode(':', $duration);
      $duration = [
        'hour' => $hour,
        'minute' => $minute,
        'second' => $second,
      ];

      $item
        ->set('title', $entry->getTitle())
        ->set('duration', $duration)
        ->set('keywords', $keywords)
        ->set('guid', $entry->getId())
        ->set('url', $entry->getLink())
        ->set('guid', $entry->getId())
        ->set('url', $entry->getLink())
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
    $mapping_sources = parent::getMappingSources();
    $mapping_sources['duration'] = [
      'label' => $this->t('Duration'),
      'description' => $this->t('The duration of the episode.'),
    ];
    $mapping_sources['episode'] = [
      'label' => $this->t('Episode ID'),
      'description' => $this->t('The episode ID.'),
    ];
    $mapping_sources['keywords'] = [
      'label' => $this->t('Keywords'),
      'description' => $this->t('The duration of the episode.'),
    ];
    return $mapping_sources;
  }

}
