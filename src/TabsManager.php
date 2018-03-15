<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * TabsManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;
use Exception;

/**
 * Class TabsManager.
 *
 * @package Drupal\adminic_toolbar
 */
class TabsManager {

  /**
   * Discovery manager.
   *
   * @var \Drupal\adminic_toolbar\DiscoveryManager
   */
  private $discoveryManager;

  /**
   * Route manager.
   *
   * @var \Drupal\adminic_toolbar\RouteManager
   */
  private $routeManager;

  /**
   * Tabs.
   *
   * @var array
   */
  private $tabs = [];

  /**
   * Active tabs.
   *
   * @var array
   */
  private $activeTabs = [];

  /**
   * Class that manages modules in a Drupal installation.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * TabsManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   *   Discovery manager.
   * @param \Drupal\adminic_toolbar\RouteManager $routeManager
   *   Route manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   */
  public function __construct(
    DiscoveryManager $discoveryManager,
    RouteManager $routeManager,
    ModuleHandler $moduleHandler) {
    $this->discoveryManager = $discoveryManager;
    $this->routeManager = $routeManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Add tab to active tabs.
   *
   * @param \Drupal\adminic_toolbar\Tab $tab
   *   Tab.
   */
  public function addActiveTab(Tab $tab) {
    $key = $this->getTabKey($tab);
    $this->activeTabs[$key] = $tab;
  }

  /**
   * Get tab unique key from id.
   *
   * @param \Drupal\adminic_toolbar\Tab $tab
   *   Tab.
   *
   * @return string
   *   Return formated key.
   */
  public function getTabKey(Tab $tab) {
    return $tab->getId();
  }

  /**
   * Set tab as active.
   *
   * @param string $key
   *   Tab key.
   */
  public function setActive(string $key) {
    $this->tabs[$key]->setActive();
  }

  /**
   * Get tabs.
   *
   * @return array
   *   Return array of tabs.
   */
  public function getTabs() {
    if (empty($this->tabs)) {
      $this->parseTabs();
    }

    return $this->tabs;
  }

  /**
   * Get all defined tabs from all config files.
   */
  protected function parseTabs() {
    $config = $this->discoveryManager->getConfig();

    $weight = 0;
    $configTabs = [];
    foreach ($config as $configFile) {
      if (isset($configFile['tabs'])) {
        foreach ($configFile['tabs'] as $tab) {
          $tab['weight'] = isset($tab['weight']) ? $tab['weight'] : $weight;
          $tab['set'] = isset($tab['set']) ? $tab['set'] : 'default';
          $key = $tab['id'];
          $configTabs[$key] = $tab;
          $weight++;
        }
      }
    }

    // Sort tabs by weight.
    uasort($configTabs, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_config_tabs', $configTabs);

    // Add tabs.
    $this->addTabs($configTabs);
  }

  /**
   * Get first active tab.
   *
   * @return \Drupal\adminic_toolbar\Tab
   *   Return first active tab.
   */
  public function getActiveTab() {
    $activeTabs = $this->activeTabs;
    if ($activeTabs) {
      return reset($activeTabs);
    }

    return NULL;
  }

  /**
   * Add tabs.
   *
   * @param array $configTabs
   *   Array of tabs.
   */
  protected function addTabs(array $configTabs) {
    foreach ($configTabs as $tab) {

      $this->validateTab($tab);

      $id = $tab['id'];
      $widget_id = $tab['widget_id'];
      $route = $tab['route'];
      $route_params = isset($tab['route_params']) ? $tab['route_params'] : [];
      $isValid = $this->routeManager->isRouteValid($route, $route_params);

      if ($isValid && $tab['set'] == $this->discoveryManager->getActiveSet()) {
        $title = isset($tab['title']) ? $tab['title'] : $this->routeManager->getDefaultTitle($route, $route_params);
        $title = empty($title) ? '' : $title;
        $url = Url::fromRoute($route, $route_params);
        $disabled = isset($tab['disabled']) ? $tab['disabled'] : FALSE;
        $badge = isset($tab['badge']) ? $tab['badge'] : '';
        $active = FALSE;
        $this->addTab(new Tab($id, $widget_id, $url, $title, $active, $disabled, $badge));
      }
    }
  }

  /**
   * Add tab.
   *
   * @param \Drupal\adminic_toolbar\Tab $tab
   *   Tab.
   */
  public function addTab(Tab $tab) {
    $key = $this->getTabKey($tab);
    $this->tabs[$key] = $tab;
    // Remove tab if exists and is disabled.
    if (isset($this->tabs[$key]) && $tab->isDisabled()) {
      unset($this->tabs[$key]);
    }

  }

  /**
   * Validate tab required parameters.
   *
   * @param array $tab
   *   Tab array.
   */
  protected function validateTab(array $tab) {
    try {
      $obj = json_encode($tab);
      if (!isset($tab['id'])) {
        throw new Exception('Tab ID parameter missing ' . $obj);
      };
      if (!isset($tab['widget_id'])) {
        throw new Exception('Tab widget_id parameter missing ' . $obj);
      };
      if (!isset($tab['route'])) {
        throw new Exception('Tab route parameter missing ' . $obj);
      }
    }
    catch (Exception $e) {
      print $e->getMessage();
    }
  }

}
