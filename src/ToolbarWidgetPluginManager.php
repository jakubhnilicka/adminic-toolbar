<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Traversable;

class ToolbarWidgetPluginManager extends DefaultPluginManager {

  public function __construct(Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {
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
