<?php

namespace Drupal\adminic_toolbar;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Traversable;

class ToolbarWidgetPluginManager extends DefaultPluginManager  {

  /**
   * @var array
   */
  private $config = [];

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

  /**
   * Load all confuguration files for toolbar and convert them to array.
   *
   * @return array
   *   Configuration parsed from yaml files.
   */
  protected function loadConfig() {
    $discovery = new YamlDiscovery('toolbar', $this->moduleHandler->getModuleDirectories());
    $config = $discovery->findAll();

    return $config;
  }

  /**
   * Get loaded config.
   *
   * @return array
   */
  public function getConfig() {
    if (empty($this->config)) {
      $this->config = $this->loadConfig();
    }

    return $this->config;
  }

}
