<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarPrimarySectionTabsManager.phpabsManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;
use Exception;

/**
 * Class ToolbarPrimarySectionTabsManager.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarPrimarySectionTabsManager {

  const TABS = 'primary_sections_tabs';
  const TAB_ID = 'id';
  const TAB_PRIMARY_SECTION = 'primary_section_id';
  const TAB_ROUTE_NAME = 'route_name';
  const TAB_ROUTE_PARAMETERS = 'route_parameters';
  const TAB_TITLE = 'title';
  const TAB_BADGE = 'badge';
  const TAB_PRESET = 'preset';
  const TAB_WEIGHT = 'weight';
  const TAB_DISABLED = 'disabled';

  /**
   * Discovery manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarConfigDiscovery
   */
  private $toolbarConfigDiscovery;

  /**
   * Route manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarRouteManager
   */
  private $toolbarRouteManager;

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
   * @param \Drupal\adminic_toolbar\ToolbarConfigDiscovery $toolbarConfigDiscovery
   *   Discovery manager.
   * @param \Drupal\adminic_toolbar\ToolbarRouteManager $toolbarRouteManager
   *   Route manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   */
  public function __construct(
    ToolbarConfigDiscovery $toolbarConfigDiscovery,
    ToolbarRouteManager $toolbarRouteManager,
    ModuleHandler $moduleHandler) {
    $this->toolbarConfigDiscovery = $toolbarConfigDiscovery;
    $this->toolbarRouteManager = $toolbarRouteManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Get all defined tabs from all config files.
   */
  protected function discoveryPrimarySectionsTabs() {
    $config = $this->toolbarConfigDiscovery->getConfig();

    $weight = 0;
    $configTabs = [];
    foreach ($config as $configFile) {
      if (isset($configFile[self::TABS])) {
        foreach ($configFile[self::TABS] as $tab) {
          $tab[self::TAB_WEIGHT] = isset($tab[self::TAB_WEIGHT]) ? $tab[self::TAB_WEIGHT] : $weight++;
          $tab[self::TAB_PRESET] = isset($tab[self::TAB_PRESET]) ? $tab[self::TAB_PRESET] : 'default';
          $key = $tab[self::TAB_ID];
          $configTabs[$key] = $tab;
        }
      }
    }

    // Sort tabs by weight.
    uasort($configTabs, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_config_tabs', $configTabs);

    // Add tabs.
    $this->createTabsCollection($configTabs);
  }

  /**
   * Add tabs.
   *
   * @param array $configTabs
   *   Array of tabs.
   */
  protected function createTabsCollection(array $configTabs) {
    $activeRoutes = $this->toolbarRouteManager->getActiveRoutes();

    foreach ($configTabs as $tab) {

      $this->validateTab($tab);

      $id = $tab[self::TAB_ID];
      $primarySectionId = $tab[self::TAB_PRIMARY_SECTION];
      $routeName = $tab[self::TAB_ROUTE_NAME];
      $routeParameters = isset($tab[self::TAB_ROUTE_PARAMETERS]) ? $tab[self::TAB_ROUTE_PARAMETERS] : [];
      $isRouteValid = $this->toolbarRouteManager->isRouteValid($routeName, $routeParameters);

      if ($isRouteValid && $tab[self::TAB_PRESET] == $this->toolbarConfigDiscovery->getActiveSet()) {
        $title = isset($tab[self::TAB_TITLE]) ? $tab[self::TAB_TITLE] : $this->toolbarRouteManager->getDefaultTitle($routeName, $routeParameters);
        $title = empty($title) ? '' : $title;
        $url = Url::fromRoute($routeName, $routeParameters);
        $disabled = isset($tab[self::TAB_DISABLED]) ? $tab[self::TAB_DISABLED] : FALSE;
        $badge = isset($tab[self::TAB_BADGE]) ? $tab[self::TAB_BADGE] : '';
        $active = FALSE;
        if (array_key_exists($routeName, $activeRoutes)) {
          $active = TRUE;
        }
        $this->addTab(new ToolbarPrimarySectionTab($id, $primarySectionId, $url, $title, $active, $disabled, $badge));
      }
    }
  }

  /**
   * Add tab.
   *
   * @param \Drupal\adminic_toolbar\ToolbarPrimarySectionTab $tab
   *   Tab.
   */
  public function addTab(ToolbarPrimarySectionTab $tab) {
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
      if (!isset($tab[self::TAB_ID])) {
        throw new Exception('Tab ID parameter missing ' . $obj);
      };
      if (!isset($tab[self::TAB_PRIMARY_SECTION])) {
        throw new Exception('Tab widget_id parameter missing ' . $obj);
      };
      if (!isset($tab[self::TAB_ROUTE_NAME])) {
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
      $this->discoveryPrimarySectionsTabs();
    }

    return $this->tabs;
  }

  /**
   * Add tab to active tabs.
   *
   * @param \Drupal\adminic_toolbar\ToolbarPrimarySectionTab $tab
   *   Tab.
   */
  public function addActiveTab(ToolbarPrimarySectionTab $tab) {
    $key = $this->getTabKey($tab);
    $this->activeTabs[$key] = $tab;
  }

  /**
   * Get tab unique key from id.
   *
   * @param \Drupal\adminic_toolbar\ToolbarPrimarySectionTab $tab
   *   Tab.
   *
   * @return string
   *   Return formated key.
   */
  public function getTabKey(ToolbarPrimarySectionTab $tab) {
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
   * @return \Drupal\adminic_toolbar\ToolbarPrimarySectionTab
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
