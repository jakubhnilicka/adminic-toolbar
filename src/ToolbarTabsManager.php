<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarTabsManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;
use Exception;

/**
 * Class ToolbarTabsManager.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarTabsManager {

  const YML_TABS_KEY = 'primary_sections_tabs';
  const YML_TAB_WEIGHT_KEY = 'weight';
  const YML_TAB_PRESET_KEY = 'preset';
  const YML_TABS_ID_KEY = 'id';
  const YML_TABS_PRIMARY_SECTION_KEY = 'primary_section_id';
  const YML_TABS_ROUTE_NAME_KEY = 'route_name';
  const YML_TABS_ROUTE_PARAMETERS_KEY = 'route_parameters';
  const YML_TABS_TITLE_KEY = 'title';
  const YML_TABS_DISABLED_KEY = 'disabled';
  const YML_TABS_BADGE_KEY = 'badge';

  /**
   * Discovery manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarConfigDiscovery
   */
  private $discoveryManager;

  /**
   * Route manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarRouteManager
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
   * @param \Drupal\adminic_toolbar\ToolbarConfigDiscovery $discoveryManager
   *   Discovery manager.
   * @param \Drupal\adminic_toolbar\ToolbarRouteManager $routeManager
   *   Route manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   */
  public function __construct(
    ToolbarConfigDiscovery $discoveryManager,
    ToolbarRouteManager $routeManager,
    ModuleHandler $moduleHandler) {
    $this->discoveryManager = $discoveryManager;
    $this->routeManager = $routeManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Get all defined tabs from all config files.
   */
  protected function parseTabs() {
    $config = $this->discoveryManager->getConfig();

    $weight = 0;
    $configTabs = [];
    foreach ($config as $configFile) {
      if (isset($configFile[self::YML_TABS_KEY])) {
        foreach ($configFile[self::YML_TABS_KEY] as $tab) {
          $tab[self::YML_TAB_WEIGHT_KEY] = isset($tab[self::YML_TAB_WEIGHT_KEY]) ? $tab[self::YML_TAB_WEIGHT_KEY] : $weight++;
          $tab[self::YML_TAB_PRESET_KEY] = isset($tab[self::YML_TAB_PRESET_KEY]) ? $tab[self::YML_TAB_PRESET_KEY] : 'default';
          $key = $tab[self::YML_TABS_ID_KEY];
          $configTabs[$key] = $tab;
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
   * Add tabs.
   *
   * @param array $configTabs
   *   Array of tabs.
   */
  protected function addTabs(array $configTabs) {
    foreach ($configTabs as $tab) {

      $this->validateTab($tab);

      $id = $tab[self::YML_TABS_ID_KEY];
      $primarySectionId = $tab[self::YML_TABS_PRIMARY_SECTION_KEY];
      $routeName = $tab[self::YML_TABS_ROUTE_NAME_KEY];
      $routeParameters = isset($tab[self::YML_TABS_ROUTE_PARAMETERS_KEY]) ? $tab[self::YML_TABS_ROUTE_PARAMETERS_KEY] : [];
      $isRouteValid = $this->routeManager->isRouteValid($routeName, $routeParameters);

      if ($isRouteValid && $tab[self::YML_TAB_PRESET_KEY] == $this->discoveryManager->getActiveSet()) {
        $title = isset($tab[self::YML_TABS_TITLE_KEY]) ? $tab[self::YML_TABS_TITLE_KEY] : $this->routeManager->getDefaultTitle($routeName, $routeParameters);
        $title = empty($title) ? '' : $title;
        $url = Url::fromRoute($routeName, $routeParameters);
        $disabled = isset($tab[self::YML_TABS_DISABLED_KEY]) ? $tab[self::YML_TABS_DISABLED_KEY] : FALSE;
        $badge = isset($tab[self::YML_TABS_BADGE_KEY]) ? $tab[self::YML_TABS_BADGE_KEY] : '';
        $active = FALSE;
        $this->addTab(new ToolbarTab($id, $primarySectionId, $url, $title, $active, $disabled, $badge));
      }
    }
  }

  /**
   * Add tab.
   *
   * @param \Drupal\adminic_toolbar\ToolbarTab $tab
   *   Tab.
   */
  public function addTab(ToolbarTab $tab) {
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
      if (!isset($tab[self::YML_TABS_ID_KEY])) {
        throw new Exception('Tab ID parameter missing ' . $obj);
      };
      if (!isset($tab[self::YML_TABS_PRIMARY_SECTION_KEY])) {
        throw new Exception('Tab widget_id parameter missing ' . $obj);
      };
      if (!isset($tab[self::YML_TABS_ROUTE_NAME_KEY])) {
        throw new Exception('Tab route parameter missing ' . $obj);
      }
    }
    catch (Exception $e) {
      print $e->getMessage();
    }
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
   * Add tab to active tabs.
   *
   * @param \Drupal\adminic_toolbar\ToolbarTab $tab
   *   Tab.
   */
  public function addActiveTab(ToolbarTab $tab) {
    $key = $this->getTabKey($tab);
    $this->activeTabs[$key] = $tab;
  }

  /**
   * Get tab unique key from id.
   *
   * @param \Drupal\adminic_toolbar\ToolbarTab $tab
   *   Tab.
   *
   * @return string
   *   Return formated key.
   */
  public function getTabKey(ToolbarTab $tab) {
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
   * Get first active tab.
   *
   * @return \Drupal\adminic_toolbar\ToolbarTab
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
