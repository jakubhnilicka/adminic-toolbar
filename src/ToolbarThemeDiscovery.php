<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarThemeDiscovery.php.
 */

use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ToolbarThemeDiscovery.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarThemeDiscovery {

  private $themes = [];

  /**
   * Interface for classes that manage a set of enabled modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * ToolbarThemeDiscovery constructor.
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
  public function getThemes() {
    $discovery = new YamlDiscovery('libraries', $this->moduleHandler->getModuleDirectories());
    $configs = $discovery->findAll();
    $themes = [];
    foreach ($configs as $librariesNamespace => $libraries) {
      $themes[] = $this->getAdminicToolbarThemeLibraries($libraries, $librariesNamespace);
    }
    $themes = array_merge(...$themes);
    $this->themes = $themes;
    return $themes;
  }

  /**
   * Filter adminic toolbar themes from one file.
   *
   * @param array $libraries
   *   Libraries in file.
   * @param string $libraryNamespace
   *   Namespace of file.
   *
   * @return array
   *   Array of adminic toolbar themes.
   */
  protected function getAdminicToolbarThemeLibraries(array $libraries, string $libraryNamespace) {
    $themes = [];
    foreach ($libraries as $libraryIndex => $library) {
      if (strpos($libraryIndex, 'adminic_toolbar.theme', 0) !== FALSE) {
        $index = sprintf('%s/%s', $libraryNamespace, $libraryIndex);
        $themes[$index] = $library['name'];
      }
    }

    return $themes;
  }

}
