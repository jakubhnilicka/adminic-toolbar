<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;

class DiscoveryManager {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * @var array
   */
  private $config = [];

  /**
   * DiscoveryManager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Load all confuguration files for toolbar and convert them to array.
   *
   * @return array
   *   Configuration parsed from yaml files.
   */
  protected function loadConfig() {
    $discovery = new YamlDiscovery('toolbar', $this->moduleHandler->getModuleDirectories());
    $configs = $discovery->findAll();

    foreach ($configs as $key => $config) {
      // Allways load adminic toolbar before others.
      if ($key == 'adminic_toolbar') {
        $configs[$key]['weight'] = -99;
      }
      // If weight is not specified set it as 0.
      if (!isset($configs[$key]['weight'])) {
        $configs[$key]['weight'] = 0;
      }
    }
    // Sort by weight.
    uasort($configs, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $configs;
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

  public function getActiveSet() {
    return 'default';
  }

}