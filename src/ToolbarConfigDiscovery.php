<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * DiscoveryManager.php.
 */

use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ToolbarConfigDiscovery.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarConfigDiscovery {

  /**
   * Interface for classes that manage a set of enabled modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Configuration array.
   *
   * @var array
   */
  private $config = [];

  /**
   * DiscoveryManager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Interface for classes that manage a set of enabled modules.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Load all configuration files for toolbar and convert them to array.
   *
   * @return array
   *   Configuration parsed from yaml files.
   *
   * @throws \Drupal\Component\Discovery\DiscoveryException
   */
  protected function loadConfig() {
    $discovery = new YamlDiscovery('toolbar', $this->moduleHandler->getModuleDirectories());
    $configs = $discovery->findAll();

    // Add computed weight to every config file.
    foreach ($configs as $key => $config) {
      // Allways load adminic toolbar before others.
      if ($key === 'adminic_toolbar') {
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
   *   Return config array.
   *
   * @throws \Drupal\Component\Discovery\DiscoveryException
   */
  public function getConfig() {
    if (empty($this->config)) {
      $this->config = $this->loadConfig();
    }

    return $this->config;
  }

  /**
   * Get activated set.
   *
   * @return string
   *   Return set machine name.
   */
  public function getActiveSet() {
    // TODO: Allow working with sets.
    return 'default';
  }

}
