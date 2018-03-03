<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Extension\ModuleHandler;

class TabManager {

  /**
   * @var \Drupal\adminic_toolbar\DiscoveryManager
   */
  private $discoveryManager;

  /**
   * @var \Drupal\adminic_toolbar\RouteManager
   */
  private $routeManager;

  /**
   * @var array
   */
  private $tabs = [];

  /**
   * @var array
   */
  private $activeTabs = [];

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * TabManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   * @param \Drupal\adminic_toolbar\RouteManager $routeManager
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
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
   */
  public function setActive(string $key) {
    $this->tabs[$key]->setActive();
  }

  /**
   * Get tabs.
   *
   * @return array
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

    uasort($configTabs, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    $this->moduleHandler->alter('toolbar_config_tabs', $configTabs);

    foreach ($configTabs as $tab) {
      $id = $tab['id'];
      $section = isset($tab['section']) ? $tab['section'] : '';
      $route = $tab['route'];
      $isValid = $this->routeManager->isRouteValid($route);
      if ($isValid && $tab['set'] == $this->discoveryManager->getActiveSet()) {
        $title = isset($tab['title']) ? $tab['title'] : $this->routeManager->getDefaultTitle($route);
        $disabled = isset($tab['disabled']) ? $tab['disabled'] : FALSE;
        $active = FALSE;
        $this->addTab(new Tab($id, $section, $route, $title, $active, $disabled));
      }
    }
  }

  /**
   * Add tab.
   *
   * @param \Drupal\adminic_toolbar\Tab $tab
   */
  public function addTab(Tab $tab) {
    $key = $this->getTabKey($tab);
    $this->tabs[$key] = $tab;
    // Remove tab if exists and is disabled
    if (isset($this->tabs[$key]) && $tab->isDisabled()) {
      unset($this->tabs[$key]);
    }

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

}
