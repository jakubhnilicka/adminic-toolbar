<?php
/**
 * Created by PhpStorm.
 * User: jakubhnilicka
 * Date: 08.02.18
 * Time: 21:01
 */

namespace Drupal\adminic_toolbar;

use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;

class DiscoveryManager {

  /**
   * @var array
   */
  private $config;
  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * DiscoveryManager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
    $this->config = $this->loadConfig();
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
    return $this->config;
  }

}