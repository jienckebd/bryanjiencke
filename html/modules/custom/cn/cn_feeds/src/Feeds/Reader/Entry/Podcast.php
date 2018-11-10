<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Drupal\cn_feeds\Feeds\Reader\Entry;

use DateTime;
use DOMElement;
use DOMXPath;
use Zend\Feed\Reader;
use Zend\Feed\Reader\Exception;
use Zend\Feed\Reader\Entry\Rss;

class Podcast extends Rss {

  /**
   * Get the entry keywords
   *
   * @return string
   */
  public function getKeywords()
  {
    if (isset($this->data['keywords'])) {
      return $this->data['keywords'];
    }

    $keywords = $this->xpath->evaluate('string(' . $this->getXpathPrefix() . '/itunes:keywords)');

    if (! $keywords) {
      $keywords = null;
    }

    $this->data['keywords'] = $keywords;

    return $this->data['keywords'];
  }

}
