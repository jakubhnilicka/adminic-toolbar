<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * DiscoveryManager.php.
 */
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

  private $extend = [];

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
    $discovery = new ToolbarYamlDiscovery('toolbar', $this->moduleHandler->getModuleDirectories());
    $configs = $discovery->findAll();

    $this->initExtend($configs);
    // Add computed weight to every config file.
    foreach ($configs as $key => $config) {
      $canLoadConfig = $this->canLoadConfig($key);
      list($provider, $preset) = explode('.', $key);

      if ($canLoadConfig !== TRUE) {
        unset($configs[$key]);
        continue;
      }
      // Allways load adminic toolbar before others.
      if ($provider === 'adminic_toolbar') {
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
   * Initialize extended keys.
   *
   * @param array $configs
   *   Configurations.
   */
  protected function initExtend(array $configs) {
    foreach ($configs as $key => $config) {
      list($provider, $preset) = explode('.', $key);
      // If config preset is active.
      if (isset($config['preset']['extend']) && $preset === $this->getActiveSet()) {
        $this->extend[] = $config['preset']['extend'];
      }
    }
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
    return 'default';
  }

  /**
   * Check if config can be loaded.
   *
   * Only configs which have active preset key
   * or extended source are loaded.
   *
   * @param string $key
   *   Configuration key.
   * @param $config
   *   Configuration array.
   *
   * @return bool
   *   True if can load config or false.
   */
  protected function canLoadConfig(string $key) {
    list($provider, $preset) = explode('.', $key);

    // If config preset is active.
    if ($preset === $this->getActiveSet()) {
      return TRUE;
    }

    // If config preset is in extend source.
    if (in_array($preset, $this->extend)) {
      return TRUE;
    }

    return FALSE;
  }

}
