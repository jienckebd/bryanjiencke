<?php

namespace Drupal\cn_core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\address\Event\AddressEvents;

class AddressFieldSubscriber implements EventSubscriberInterface {

  static function getSubscribedEvents() {
    $events[AddressEvents::ADDRESS_FORMAT][] = array('onGetDefinition', 0);
    return $events;
  }

  public function onGetDefinition($event) {
    $definition = $event->getDefinition();
    // This makes city (locality) field required and leaves
    // the rest address fields as optional
    $definition['required_fields'] = [];
    $event->setDefinition($definition);
  }

}
