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