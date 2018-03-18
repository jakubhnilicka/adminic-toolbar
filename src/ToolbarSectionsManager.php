<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarSectionsManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Exception;

/**
 * Class ToolbarSectionsManager.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarSectionsManager {

  const PRIMARY_SECTIONS = 'primary_sections';
  const SECONDARY_SECTIONS = 'secondary_sections';
  const YML_SECTION_WEIGHT_KEY = 'weight';
  const YML_SECTION_PRESET_KEY = 'preset';
  const YML_SECTION_ID_KEY = 'id';
  const YML_SECTION_TITLE_KEY = 'title';
  const YML_SECTION_TAB_ID_KEY = 'tab_id';
  const YML_SECTION_DISABLED_KEY = 'disabled';
  const YML_SECTION_PLUGIN_ID_KEY = 'plugin_id';

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
   * Links manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarLinksManager
   */
  private $linkManager;

  /**
   * Tabs manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarTabsManager
   */
  private $tabManager;

  /**
   * Sections.
   *
   * @var array
   */
  private $sections = [];

  /**
 * Primary Sections.
 *
 * @var array
 */
  private $primarySections = [];

  /**
   * Secondary Sections.
   *
   * @var array
   */
  private $secondarySections = [];

  /**
   * Active sections.
   *
   * @var array
   */
  private $activeSections = [];

  /**
   * Class that manages modules in a Drupal installation.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * Toolbar widget plugin manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarPluginManager
   */
  private $toolbarWidgetPluginManager;

  /**
   * SectionsManager constructor.
   *
   * @param \Drupal\adminic_toolbar\ToolbarConfigDiscovery $discoveryManager
   *   Discovery manager.
   * @param \Drupal\adminic_toolbar\ToolbarRouteManager $routeManager
   *   Route manager.
   * @param \Drupal\adminic_toolbar\ToolbarLinksManager $linkManager
   *   Links manager.
   * @param \Drupal\adminic_toolbar\ToolbarTabsManager $tabManager
   *   Tabs manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   * @param \Drupal\adminic_toolbar\ToolbarPluginManager $toolbarWidgetPluginManager
   *   Toolbar widget plugin manager.
   *
   * @todo Check names for $linkManager, $tabManager, when classes are LinksManager, TabsManager.
   */
  public function __construct(
    ToolbarConfigDiscovery $discoveryManager,
    ToolbarRouteManager $routeManager,
    ToolbarLinksManager $linkManager,
    ToolbarTabsManager $tabManager,
    ModuleHandler $moduleHandler,
    ToolbarPluginManager $toolbarWidgetPluginManager) {
    $this->discoveryManager = $discoveryManager;
    $this->linkManager = $linkManager;
    $this->tabManager = $tabManager;
    $this->routeManager = $routeManager;
    $this->moduleHandler = $moduleHandler;
    $this->toolbarWidgetPluginManager = $toolbarWidgetPluginManager;
  }

  /**
   * Get all defined sections from all config files.
   *
   * @throws \Exception
   */
  protected function parsePrimarySections() {
    $this->setActiveLinks();
    $config = $this->discoveryManager->getConfig();

    $weight = 0;
    $configSections = [];
    foreach ($config as $configFile) {
      if (isset($configFile[self::PRIMARY_SECTIONS])) {
        foreach ($configFile[self::PRIMARY_SECTIONS] as $section) {
          // If weight is empty set computed value.
          $section[self::YML_SECTION_WEIGHT_KEY] = isset($section[self::YML_SECTION_WEIGHT_KEY]) ? $section[self::YML_SECTION_WEIGHT_KEY] : $weight;
          // If set is empty set default set.
          $section[self::YML_SECTION_PRESET_KEY] = isset($section[self::YML_SECTION_PRESET_KEY]) ? $section[self::YML_SECTION_PRESET_KEY] : 'default';
          // TODO: get key from method.
          $key = $section[self::YML_SECTION_ID_KEY];
          $configSections[$key] = $section;
          $weight++;
        }
      }
    }
    // Sort tabs by weight.
    uasort($configSections, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_config_primary_sections', $configSections);

    // Add tabs.
    $this->addPrimarySections($configSections);
  }

  /**
   * Add sections.
   *
   * @param array $configSections
   *   Array of sections.
   */
  protected function addPrimarySections(array $configSections) {
    $activeLink = $this->linkManager->getActiveLink();

    foreach ($configSections as $section) {
      if ($section[self::YML_SECTION_PRESET_KEY] == $this->discoveryManager->getActiveSet()) {
        $this->validateSection($section);

        $id = $section[self::YML_SECTION_ID_KEY];
        $title = isset($section[self::YML_SECTION_TITLE_KEY]) ? $section[self::YML_SECTION_TITLE_KEY] : '';
        $tab_id = isset($section[self::YML_SECTION_TAB_ID_KEY]) ? $section[self::YML_SECTION_TAB_ID_KEY] : '';
        $disabled = isset($section[self::YML_SECTION_DISABLED_KEY]) ? $section[self::YML_SECTION_DISABLED_KEY] : FALSE;
        $type = isset($section[self::YML_SECTION_PLUGIN_ID_KEY]) ? $section[self::YML_SECTION_PLUGIN_ID_KEY] : '';
        $newSection = new ToolbarSection($id, $title, $tab_id, $disabled, $type);
        $this->addPrimarySection($newSection);

        if ($activeLink && $id == $activeLink->getWidget()) {
          $this->addActiveSection($newSection);
        }
      }
    }

    $this->setActiveTabs();
  }

  /**
   * Add section.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSection $section
   *   Section.
   */
  public function addPrimarySection(ToolbarSection $section) {
    $key = $this->getSectionKey($section);
    $this->primarySections[$key] = $section;
    // Remove section if exists but is disabled.
    if (isset($this->primarySections[$key]) && $section->isDisabled()) {
      unset($this->primarySections[$key]);
    }
  }

  /**
   * Get sections defined for primary toolbar.
   *
   * @return array
   *   Array of sections.
   *
   * @throws \Exception
   */
  public function getPrimarySections() {
    if (empty($this->primarySections)) {
      $this->parsePrimarySections();
    }

    return $this->primarySections;
  }

  /**
   * Get all defined sections from all config files.
   *
   * @throws \Exception
   */
  protected function parseSecondarySections() {
    $this->setActiveLinks();
    $config = $this->discoveryManager->getConfig();

    $weight = 0;
    $configSections = [];
    foreach ($config as $configFile) {
      if (isset($configFile[self::SECONDARY_SECTIONS])) {
        foreach ($configFile[self::SECONDARY_SECTIONS] as $section) {
          // If weight is empty set computed value.
          $section[self::YML_SECTION_WEIGHT_KEY] = isset($section[self::YML_SECTION_WEIGHT_KEY]) ? $section[self::YML_SECTION_WEIGHT_KEY] : $weight++;
          // If set is empty set default set.
          $section[self::YML_SECTION_PRESET_KEY] = isset($section[self::YML_SECTION_PRESET_KEY]) ? $section[self::YML_SECTION_PRESET_KEY] : 'default';
          // TODO: get key from method.
          $key = $section[self::YML_SECTION_ID_KEY];
          $configSections[$key] = $section;
        }
      }
    }
    // Sort tabs by weight.
    uasort($configSections, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_config_primary_sections', $configSections);

    // Add tabs.
    $this->addSecondarySections($configSections);
  }

  /**
   * Add sections.
   *
   * @param array $configSections
   *   Array of sections.
   */
  protected function addSecondarySections(array $configSections) {
    $activeLink = $this->linkManager->getActiveLink();

    foreach ($configSections as $section) {
      if ($section[self::YML_SECTION_PRESET_KEY] == $this->discoveryManager->getActiveSet()) {
        $this->validateSection($section);

        $id = $section[self::YML_SECTION_ID_KEY];
        $title = isset($section[self::YML_SECTION_TITLE_KEY]) ? $section[self::YML_SECTION_TITLE_KEY] : '';
        $tab_id = isset($section[self::YML_SECTION_TAB_ID_KEY]) ? $section[self::YML_SECTION_TAB_ID_KEY] : '';
        $disabled = isset($section[self::YML_SECTION_DISABLED_KEY]) ? $section[self::YML_SECTION_DISABLED_KEY] : FALSE;
        $type = isset($section[self::YML_SECTION_PLUGIN_ID_KEY]) ? $section[self::YML_SECTION_PLUGIN_ID_KEY] : '';
        $newSection = new ToolbarSection($id, $title, $tab_id, $disabled, $type);
        $this->addSecondarySection($newSection);

        if ($activeLink && $id == $activeLink->getWidget()) {
          $this->addActiveSection($newSection);
        }
      }
    }

    $this->setActiveTabs();
  }

  /**
   * Add section.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSection $section
   *   Section.
   */
  public function addSecondarySection(ToolbarSection $section) {
    $key = $this->getSectionKey($section);
    $this->secondarySections[$key] = $section;
    // Remove section if exists but is disabled.
    if (isset($this->secondarySections[$key]) && $section->isDisabled()) {
      unset($this->secondarySections[$key]);
    }
  }

  /**
   * Get sections defined for primary toolbar.
   *
   * @return array
   *   Array of sections.
   *
   * @throws \Exception
   */
  public function getSecondarySections() {
    if (empty($this->secondarySections)) {
      $this->parseSecondarySections();
    }

    return $this->secondarySections;
  }











  /**
   * Set active links.
   *
   * @todo What do you mean, the link is active?
   *
   * @throws \Exception
   */
  protected function setActiveLinks() {
    // Try to select active links from config links hierarchy.
    $this->setActiveLinksViaConfig();

    // If active link is still empty, select active link from routes.
    $activeLink = $this->linkManager->getActiveLink();
    if (empty($activeLink)) {
      $this->setActiveLinksViaRoutes();
    }
  }

  /**
   * Add section.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSection $section
   *   Section.
   */
  public function addSection(ToolbarSection $section) {
    $key = $this->getSectionKey($section);
    $this->sections[$key] = $section;
    // Remove section if exists but is disabled.
    if (isset($this->sections[$key]) && $section->isDisabled()) {
      unset($this->sections[$key]);
    }
  }

  /**
   * Get section key.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSection $section
   *   Section.
   *
   * @return string
   *   Return section key.
   */
  protected function getSectionKey(ToolbarSection $section) {
    return $section->getId();
  }

  /**
   * Add active section.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSection $section
   *   Section.
   */
  public function addActiveSection(ToolbarSection $section) {
    $this->activeSections[] = $section;
  }

  /**
   * Set active tabs.
   *
   * @todo Refactor after better understanding.
   */
  protected function setActiveTabs() {
    // Try to get active tabs from tabs hierarchy.
    $activeSections = $this->getActiveSection();
    $currentRouteName = $this->routeManager->getCurrentRoute();
    $tabs = $this->tabManager->getTabs();
    /** @var \Drupal\adminic_toolbar\ToolbarTab $tab */
    foreach ($tabs as $key => &$tab) {
      $tabUrl = $tab->getRawUrl();
      $tabRouteName = $tabUrl->getRouteName();
      if ($activeSections && $tab->getId() == $activeSections->getTab()) {
        $tab->setActive();
        $this->tabManager->addActiveTab($tab);
      }
      elseif ($tabRouteName == $currentRouteName) {
        $tab->setActive();
        $this->tabManager->addActiveTab($tab);
      }
    }

    // Set active tabs from routes.
    $activeTabs = $this->tabManager->getActiveTab();
    if (empty($activeTabs)) {
      $activeRoutes = $this->routeManager->getActiveRoutes();
      $tabs = $this->tabManager->getTabs();
      foreach ($tabs as $tab) {
        $tabUrl = $tab->getRawUrl();
        $tabRouteName = $tabUrl->getRouteName();
        if (array_key_exists($tabRouteName, $activeRoutes)) {
          $tab->setActive();
          $this->tabManager->addActiveTab($tab);
        }
      }
    }
  }

  /**
   * Get first active section.
   *
   * @return \Drupal\adminic_toolbar\ToolbarSection|null
   *   Return first active section or NULL.
   */
  public function getActiveSection() {
    $activeSections = $this->activeSections;
    if ($activeSections) {
      return reset($activeSections);
    }

    return NULL;
  }

  /**
   * Get renderable array for primary section.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSection $section
   *   Section.
   *
   * @return array|null
   *   Return renderable array or NULL.
   */
  public function getPrimarySection(ToolbarSection $section) {
    $tabs = $this->tabManager->getTabs();
    $sectionId = $section->getId();

    $sectionValidTabs = array_filter(
      $tabs, function ($tab) use ($sectionId) {
        /** @var \Drupal\adminic_toolbar\ToolbarTab $tab */
        return $tab->getWidget() == $sectionId;
      }
    );

    $sectionTabs = [];
    /** @var \Drupal\adminic_toolbar\ToolbarTab $tab */
    foreach ($sectionValidTabs as $tab) {
      $sectionTabs[] = $tab->getRenderArray();
    }

    if ($sectionTabs) {
      $section->setLinks($sectionTabs);

      return $section->getRenderArray();
    }

    return NULL;
  }

  /**
   * Get secondary sections wrappers.
   *
   * @return array
   *   Return array of secondary section wrappers.
   *
   * @throws \Exception
   */
  public function getSecondarySectionWrappers() {
    $tabs = $this->tabManager->getTabs();
    $secondaryWrappers = [];
    /** @var \Drupal\adminic_toolbar\ToolbarTab $tab */
    foreach ($tabs as $tab) {
      $sections = $this->getSecondarySectionsByTab($tab);

      $secondaryWrappers[$tab->getId()] = [
        'title' => $tab->getTitle(),
        'route' => $tab->getUrl(),
        'sections' => $sections,
      ];
    }

    return $secondaryWrappers;
  }

  /**
   * Get secondary sections by tab.
   *
   * @param \Drupal\adminic_toolbar\ToolbarTab $tab
   *   Tab.
   *
   * @return array
   *   Return array of secondary sections for specified tab.
   *
   * @throws \Exception
   */
  protected function getSecondarySectionsByTab(ToolbarTab $tab) {
    $renderedSections = [];
    $sections = $this->getSecondarySections();

    /** @var \Drupal\adminic_toolbar\ToolbarTab $tab */
    $secondarySections = array_filter(
      $sections, function ($section) use ($tab) {
        /** @var \Drupal\adminic_toolbar\ToolbarSection $section */
        $sectionTab = $section->getTab();
        return !empty($sectionTab) && $sectionTab == $tab->getId();
      }
    );

    if (!empty($secondarySections)) {
      $renderedSections = $this->getRenderedSections($secondarySections);
    }

    if ($renderedSections) {
      return $renderedSections;
    }

    return NULL;
  }

  /**
   * Get renderable array for secondary section.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSection $section
   *   Section.
   *
   * @return array|null
   *   Return renderable array or null.
   *
   * @throws \Exception
   */
  protected function getSecondarySection(ToolbarSection $section) {
    if ($section->hasType()) {
      $type = $section->getType();
      $widget = $this->toolbarWidgetPluginManager->createInstance($type);
      return $widget->getRenderArray();
    }

    $links = $this->linkManager->getLinks();
    $sectionId = $section->getId();

    $sectionValidLinks = array_filter(
      $links, function ($link) use ($sectionId) {
        /** @var \Drupal\adminic_toolbar\ToolbarLink $link */
        return $link->getWidget() == $sectionId;
      }
    );

    if (empty($sectionValidLinks)) {
      return NULL;
    }

    $sectionLinks = [];
    /** @var \Drupal\adminic_toolbar\ToolbarLink $link */
    foreach ($sectionValidLinks as $link) {
      $sectionLinks[] = $link->getRenderArray();
    }

    if ($sectionLinks) {
      $section->setLinks($sectionLinks);
      return $section->getRenderArray();
    }

    return NULL;
  }



  /**
   * Validate section required parameters.
   *
   * @param array $section
   *   Section array.
   */
  protected function validateSection(array $section) {
    try {
      $obj = json_encode($section);
      if (empty($section[self::YML_SECTION_ID_KEY])) {
        throw new Exception('Section ID parameter missing ' . $obj);
      };
    }
    catch (Exception $e) {
      print $e->getMessage();
    }
  }

  /**
   * Select active link from routes.
   *
   * @todo Find a better name.
   */
  protected function setActiveLinksViaRoutes() {
    $activeRoutes = $this->routeManager->getActiveRoutes();
    $links = $this->linkManager->getLinks();
    foreach ($links as &$link) {
      /** @var \Drupal\adminic_toolbar\ToolbarLink $link */
      $url = $link->getRawUrl();
      $linkRouteName = $url->getRouteName();
      if (array_key_exists($linkRouteName, $activeRoutes)) {
        $this->activateLinkByLink($link);
      }
    }
  }

  /**
   * Select active links from config links hierarchy.
   *
   * @todo Find a better name.
   */
  protected function setActiveLinksViaConfig() {
    $currentRouteName = $this->routeManager->getCurrentRoute();
    $links = $this->linkManager->getLinks();
    foreach ($links as &$link) {
      /** @var \Drupal\adminic_toolbar\ToolbarLink $link */
      $url = $link->getRawUrl();
      $linkRouteName = $url->getRouteName();
      if ($linkRouteName == $currentRouteName) {
        $this->activateLinkByLink($link);
      }
    }
  }

  /**
   * Set link to active and add it to active links.
   *
   * @param \Drupal\adminic_toolbar\ToolbarLink $link
   *   Link.
   */
  protected function activateLinkByLink(ToolbarLink $link) {
    $link->setActive();
    $this->linkManager->addActiveLink($link);
  }

  /**
   * Get rendered section.
   *
   * @param array $secondarySections
   *   Array keyed by what?
   *
   * @todo Add better explanation.
   *
   * @return array
   *   Array of renderable arrays.
   */
  protected function getRenderedSections(array $secondarySections) {
    $renderedSections = [];
    foreach ($secondarySections as $key => $secondarySection) {
      $section = $this->getSecondarySection($secondarySection);
      if ($section) {
        $renderedSections[$key] = $section;
      }
    }
    return $renderedSections;
  }

}
