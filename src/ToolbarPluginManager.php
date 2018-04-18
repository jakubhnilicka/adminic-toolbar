<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarPluginManager.php.
 */

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Traversable;
use Drupal\adminic_toolbar\Annotation\ToolbarPlugin;

/**
 * Class ToolbarPluginManager.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarPluginManager extends DefaultPluginManager {

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
      'Plugin/ToolbarPlugin',
      $namespaces,
      $module_handler,
      ToolbarPluginInterface::class,
      ToolbarPlugin::class
    );

    $this->alterInfo('toolbar_plugin_info');
    $this->setCacheBackend($cache_backend, 'toolbar_plugins');
  }

}
