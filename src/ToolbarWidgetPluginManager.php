<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarWidgetPluginManager.php.
 */

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Traversable;

/**
 * Class ToolbarWidgetPluginManager.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarWidgetPluginManager extends DefaultPluginManager {

  /**
   * ToolbarWidgetPluginManager constructor.
   *
   * @param \Traversable $namespaces
   *   Interface to detect if a class is traversable using &foreach.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Defines an interface for cache implementations.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Interface for classes that manage a set of enabled modules.
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ToolbarWidget',
      $namespaces,
      $module_handler,
      'Drupal\adminic_toolbar\ToolbarWidgetPluginInterface',
      'Drupal\adminic_toolbar\Annotation\ToolbarWidgetPlugin'
    );

    $this->alterInfo('toolbar_widget_info');
    $this->setCacheBackend($cache_backend, 'toolbar_widget_plugins');
  }

}
