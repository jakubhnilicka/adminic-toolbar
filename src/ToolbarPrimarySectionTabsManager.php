<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarPrimarySectionTabsManager.phpabsManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;
use Exception;
use RuntimeException;

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
   *
   * @throws \Drupal\Component\Discovery\DiscoveryException
   */
  protected function discoveryPrimarySectionsTabs() {
    $config = $this->toolbarConfigDiscovery->getConfig();

    $weight = 0;
    $configPrimarySectionsTabs = [];
    foreach ($config as $configFile) {
      if (isset($configFile[self::TABS])) {
        /** @var array $configFileTabs */
        $configFileTabs = $configFile[self::TABS];
        foreach ($configFileTabs as $tab) {
          $tab[self::TAB_WEIGHT] = $tab[self::TAB_WEIGHT] ?? $weight++;
          $tab[self::TAB_PRESET] = $tab[self::TAB_PRESET] ?? 'default';
          $key = $tab[self::TAB_ID];
          $configPrimarySectionsTabs[$key] = $tab;
        }
      }
    }

    // Sort tabs by weight.
    uasort($configPrimarySectionsTabs, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_primary_sections_tabs', $configPrimarySectionsTabs);

    // Add tabs.
    $this->createTabsCollection($configPrimarySectionsTabs);
  }

  /**
   * Add tabs.
   *
   * @param array $configTabs
   *   Array of tabs.
   */
  protected function createTabsCollection(array $configTabs) {
    $activeSection = $this->toolbarRouteManager->getActiveSecondarySection();

    $activeTabs = [];
    // Set by active section.
    if (!empty($activeSection)) {
      array_walk($configTabs, function ($tab) use ($activeSection, &$activeTabs) {
        if ($tab['id'] === $activeSection['tab_id']) {
          $activeTabs[$tab['id']] = $tab;
        }
      });
    }

    // Set by current route.
    if (empty($activeTabs)) {
      $activeRoutesName = $this->toolbarRouteManager->getCurrentRoute();
      array_walk($configTabs, function ($tab) use ($activeRoutesName, &$activeTabs) {
        if ($tab['route_name'] === $activeRoutesName) {
          $activeTabs[$tab['id']] = $tab;
        }
      });
    }

    // Set by path trail.
    if (empty($activeTabs)) {
      $activeRoutes = $this->toolbarRouteManager->getActiveRoutesByPath();
      array_walk($configTabs, function ($tab) use ($activeRoutes, &$activeTabs) {
        if (array_key_exists($tab['route_name'], $activeRoutes)) {
          $activeTabs[$tab['id']] = $tab;
        }
      });
    }

    foreach ($configTabs as $tab) {

      $this->validateTab($tab);

      $id = $tab[self::TAB_ID];
      $primarySectionId = $tab[self::TAB_PRIMARY_SECTION];
      $routeName = $tab[self::TAB_ROUTE_NAME];
      $routeParameters = $tab[self::TAB_ROUTE_PARAMETERS] ?? [];
      $isRouteValid = $this->toolbarRouteManager->isRouteValid($routeName, $routeParameters);

      if ($isRouteValid && $tab[self::TAB_PRESET] === $this->toolbarConfigDiscovery->getActiveSet()) {
        $title = $tab[self::TAB_TITLE] ?? $this->toolbarRouteManager->getDefaultTitle($routeName, $routeParameters);
        $title = empty($title) ? '' : $title;
        $url = Url::fromRoute($routeName, $routeParameters);
        $disabled = $tab[self::TAB_DISABLED] ?? FALSE;
        $badge = $tab[self::TAB_BADGE] ?? '';
        $active = FALSE;
        if (array_key_exists($id, $activeTabs)) {
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
        throw new RuntimeException('Tab ID parameter missing ' . $obj);
      }
      if (!isset($tab[self::TAB_PRIMARY_SECTION])) {
        throw new RuntimeException('Tab widget_id parameter missing ' . $obj);
      }
      if (!isset($tab[self::TAB_ROUTE_NAME])) {
        throw new RuntimeException('Tab route parameter missing ' . $obj);
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
   *
   * @throws \Drupal\Component\Discovery\DiscoveryException
   */
  public function getTabs() {
    $tabs = &drupal_static(__FUNCTION__);
    if (!$tabs) {
      $this->discoveryPrimarySectionsTabs();
      $tabs = $this->tabs;
    }

    return $tabs;
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
   * Get first active tab.
   *
   * @return \Drupal\adminic_toolbar\ToolbarPrimarySectionTab
   *   Return first active tab.
   */
  public function getActiveTab() {
    $activeSecondarySection = $this->toolbarRouteManager->getActiveSecondarySection();
    if ($activeSecondarySection) {
      return $activeSecondarySection['tab_id'];
    }
    return NULL;
  }

}
