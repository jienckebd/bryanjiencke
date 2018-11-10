<?php

namespace Drupal\cn_feeds\Feeds\Reader\Feed;

use DateTime;
use DOMDocument;
use Zend\Feed\Reader;
use Zend\Feed\Reader\Collection;
use Zend\Feed\Reader\Exception;
use Zend\Feed\Reader\Feed\Rss;

/**
 */
class Article extends Rss {

  /**
   * Get the entry author
   *
   * @return string
   */
  public function getMediaContent()
  {
    if (isset($this->data['media'])) {
      return $this->data['media'];
    }

    $author = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/itunes:author)');

    if (! $author) {
      $author = null;
    }

    $this->data['author'] = $author;

    return $this->data['author'];
  }

  /**
   * Get the entry block
   *
   * @return string
   */
  public function getContent()
  {
    if (isset($this->data['block'])) {
      return $this->data['block'];
    }

    $block = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/itunes:block)');

    if (! $block) {
      $block = null;
    }

    $this->data['block'] = $block;

    return $this->data['block'];
  }

}
