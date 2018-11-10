<?php

namespace Drupal\cn_feeds\Feeds\Reader;

use DOMDocument;
use DOMXPath;
use Zend\Http as ZendHttp;
use Zend\Stdlib\ErrorHandler;
use Zend\Feed\Reader\Exception\InvalidHttpClientException;
use Zend\Feed\Reader\Reader as Base;
use Zend\Feed\Exception\InvalidArgumentException;
use Zend\Feed\Exception\RuntimeException;
use Zend\Feed\Reader\Feed\Rss;
use Zend\Feed\Reader\Entry\Atom as EntryAtom;
use Zend\Feed\Reader\Feed\Atom as FeedAtom;
use Drupal\cn_feeds\Feeds\Reader\Feed\Podcast as PodcastFeed;
use Drupal\cn_feeds\Feeds\Reader\Entry\Podcast as PodcastEntry;
use Zend\Feed\Reader\Extension\Podcast\Entry as ZendPodcastEntry;

/**
 */
class PodcastReader extends Base {

  /**
   * Import a feed from a string
   *
   * @param  string $string
   * @return \Zend\Feed\Reader\Feed\FeedInterface
   * @throws \Zend\Feed\Reader\Exception\InvalidArgumentException
   * @throws \Zend\Feed\Reader\Exception\RuntimeException
   */
  public static function importString($string)
  {
    $trimmed = trim($string);
    if (! is_string($string) || empty($trimmed)) {
      throw new InvalidArgumentException('Only non empty strings are allowed as input');
    }

    $libxmlErrflag = libxml_use_internal_errors(true);
    $oldValue = libxml_disable_entity_loader(true);
    $dom = new DOMDocument;
    $status = $dom->loadXML(trim($string));
    foreach ($dom->childNodes as $child) {
      if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
        throw new InvalidArgumentException(
          'Invalid XML: Detected use of illegal DOCTYPE'
        );
      }
    }
    libxml_disable_entity_loader($oldValue);
    libxml_use_internal_errors($libxmlErrflag);

    if (! $status) {
      // Build error message
      $error = libxml_get_last_error();
      if ($error && $error->message) {
        $error->message = trim($error->message);
        $errormsg = "DOMDocument cannot parse XML: {$error->message}";
      } else {
        $errormsg = "DOMDocument cannot parse XML: Please check the XML document's validity";
      }
      throw new RuntimeException($errormsg);
    }

    $type = static::detectType($dom);

    static::registerCoreExtensions();

    if (substr($type, 0, 3) == 'rss') {
      $reader = new PodcastFeed($dom, $type);
    } elseif (substr($type, 8, 5) == 'entry') {
      $reader = new EntryAtom($dom->documentElement, 0, self::TYPE_ATOM_10);
    } elseif (substr($type, 0, 4) == 'atom') {
      $reader = new FeedAtom($dom, $type);
    } else {
      throw new RuntimeException('The URI used does not point to a '
        . 'valid Atom, RSS or RDF feed that Zend\Feed\Reader can parse.');
    }
    return $reader;
  }

}
