<?php

namespace Drupal\cn_display\Plugin\DisplayVariant;

use Drupal\block\BlockRepositoryInterface;
use Drupal\block\Plugin\DisplayVariant\BlockPageVariant;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Allows blocks to be placed directly within a region.
 *
 * @PageDisplayVariant(
 *   id = "modal_response",
 *   admin_label = @Translation("Modal Response")
 * )
 */
class ModalResponse extends PanelsDisplayVariant {
}
