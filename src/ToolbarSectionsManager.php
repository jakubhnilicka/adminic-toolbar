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
  const SECTION_ID = 'id';
  const SECTION_TAB_ID = 'tab_id';
  const SECTION_PLUGIN_ID = 'plugin_id';
  const SECTION_TITLE = 'title';
  const SECTION_PRESET = 'preset';
  const SECTION_WEIGHT = 'weight';
  const SECTION_DISABLED = 'disabled';

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
   * Links manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarLinksManager
   */
  private $toolbarLinkManager;

  /**
   * Tabs manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarTabsManager
   */
  private $toolbarTabManager;

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
  private $toolbarPluginManager;

  /**
   * SectionsManager constructor.
   *
   * @param \Drupal\adminic_toolbar\ToolbarConfigDiscovery $toolbarConfigDiscovery
   *   Toolbar config discovery.
   * @param \Drupal\adminic_toolbar\ToolbarRouteManager $toolbarRouteManager
   *   Toolbar route manager.
   * @param \Drupal\adminic_toolbar\ToolbarLinksManager $toolbarLinksManager
   *   Toolbar links manager.
   * @param \Drupal\adminic_toolbar\ToolbarTabsManager $toolbarTabsManager
   *   Toolbar tabs manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   * @param \Drupal\adminic_toolbar\ToolbarPluginManager $toolbarPluginManager
   *   Toolbar widget plugin manager.
   */
  public function __construct(
    ToolbarConfigDiscovery $toolbarConfigDiscovery,
    ToolbarRouteManager $toolbarRouteManager,
    ToolbarLinksManager $toolbarLinksManager,
    ToolbarTabsManager $toolbarTabsManager,
    ModuleHandler $moduleHandler,
    ToolbarPluginManager $toolbarPluginManager) {
    $this->toolbarConfigDiscovery = $toolbarConfigDiscovery;
    $this->toolbarLinkManager = $toolbarLinksManager;
    $this->toolbarTabManager = $toolbarTabsManager;
    $this->toolbarRouteManager = $toolbarRouteManager;
    $this->moduleHandler = $moduleHandler;
    $this->toolbarPluginManager = $toolbarPluginManager;
  }

  /**
   * Get all defined sections from all config files.
   *
   * @throws \Exception
   */
  protected function discoveryPrimarySections() {
    $this->setActiveLinksTrail();
    $config = $this->toolbarConfigDiscovery->getConfig();

    $weight = 0;
    $configSections = [];
    foreach ($config as $configFile) {
      if (isset($configFile[self::PRIMARY_SECTIONS])) {
        foreach ($configFile[self::PRIMARY_SECTIONS] as $section) {
          // If weight is empty set computed value.
          $section[self::SECTION_WEIGHT] = isset($section[self::SECTION_WEIGHT]) ? $section[self::SECTION_WEIGHT] : $weight;
          // If set is empty set default set.
          $section[self::SECTION_PRESET] = isset($section[self::SECTION_PRESET]) ? $section[self::SECTION_PRESET] : 'default';
          // TODO: get key from method.
          $key = $section[self::SECTION_ID];
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
    $this->createPrimarySectionsCollection($configSections);
  }

  /**
   * Add sections.
   *
   * @param array $configSections
   *   Array of sections.
   */
  protected function createPrimarySectionsCollection(array $configSections) {
    foreach ($configSections as $section) {
      if ($section[self::SECTION_PRESET] == $this->toolbarConfigDiscovery->getActiveSet()) {
        $this->validateSection($section);

        $id = $section[self::SECTION_ID];
        $title = isset($section[self::SECTION_TITLE]) ? $section[self::SECTION_TITLE] : '';
        $tab_id = isset($section[self::SECTION_TAB_ID]) ? $section[self::SECTION_TAB_ID] : '';
        $disabled = isset($section[self::SECTION_DISABLED]) ? $section[self::SECTION_DISABLED] : FALSE;
        $type = isset($section[self::SECTION_PLUGIN_ID]) ? $section[self::SECTION_PLUGIN_ID] : '';
        $newSection = new ToolbarSection($id, $title, $tab_id, $disabled, $type);
        $this->addPrimarySection($newSection);
      }
    }

    // Aktivuje automaticke otevreni tabu.
    $this->setActiveTabsTrail();
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
      $this->discoveryPrimarySections();
    }

    return $this->primarySections;
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
    $tabs = $this->toolbarTabManager->getTabs();
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
   * Get all defined sections from all config files.
   *
   * @throws \Exception
   */
  protected function discoverySecondarySections() {
    $this->setActiveLinksTrail();
    $config = $this->toolbarConfigDiscovery->getConfig();

    $weight = 0;
    $configSections = [];
    foreach ($config as $configFile) {
      if (isset($configFile[self::SECONDARY_SECTIONS])) {
        foreach ($configFile[self::SECONDARY_SECTIONS] as $section) {
          // If weight is empty set computed value.
          $section[self::SECTION_WEIGHT] = isset($section[self::SECTION_WEIGHT]) ? $section[self::SECTION_WEIGHT] : $weight++;
          // If set is empty set default set.
          $section[self::SECTION_PRESET] = isset($section[self::SECTION_PRESET]) ? $section[self::SECTION_PRESET] : 'default';
          // TODO: get key from method.
          $key = $section[self::SECTION_ID];
          $configSections[$key] = $section;
        }
      }
    }
    // Sort tabs by weight.
    uasort($configSections, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_config_primary_sections', $configSections);

    // Add tabs.
    $this->createSecondarySectionsCollection($configSections);
  }

  /**
   * Add sections.
   *
   * @param array $configSections
   *   Array of sections.
   */
  protected function createSecondarySectionsCollection(array $configSections) {
    $activeLink = $this->toolbarLinkManager->getActiveLink();

    foreach ($configSections as $section) {
      if ($section[self::SECTION_PRESET] == $this->toolbarConfigDiscovery->getActiveSet()) {
        $this->validateSection($section);

        $id = $section[self::SECTION_ID];
        $title = isset($section[self::SECTION_TITLE]) ? $section[self::SECTION_TITLE] : '';
        $tab_id = isset($section[self::SECTION_TAB_ID]) ? $section[self::SECTION_TAB_ID] : '';
        $disabled = isset($section[self::SECTION_DISABLED]) ? $section[self::SECTION_DISABLED] : FALSE;
        $type = isset($section[self::SECTION_PLUGIN_ID]) ? $section[self::SECTION_PLUGIN_ID] : '';
        $newSection = new ToolbarSection($id, $title, $tab_id, $disabled, $type);
        $this->addSecondarySection($newSection);

        if ($activeLink && $id == $activeLink->getToolbarPlugin()) {
          $this->addActiveSection($newSection);
        }
      }
    }
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
      $this->discoverySecondarySections();
    }

    return $this->secondarySections;
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
    $tabs = $this->toolbarTabManager->getTabs();
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
      $widget = $this->toolbarPluginManager->createInstance($type);
      return $widget->getRenderArray();
    }

    $links = $this->toolbarLinkManager->getLinks();
    $sectionId = $section->getId();

    $sectionValidLinks = array_filter(
      $links, function ($link) use ($sectionId) {
        /** @var \Drupal\adminic_toolbar\ToolbarLink $link */
        return $link->getToolbarPlugin() == $sectionId;
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
   * Add active section.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSection $section
   *   Section.
   */
  public function addActiveSection(ToolbarSection $section) {
    $this->activeSections[] = $section;
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
   * Set active links.
   *
   * @todo What do you mean, the link is active?
   *
   * @throws \Exception
   */
  protected function setActiveLinksTrail() {
    // Try to select active links from config links hierarchy.
    $this->setActiveLinksTrailViaConfig();

    // If active link is still empty, select active link from routes.
    $activeLink = $this->toolbarLinkManager->getActiveLink();
    if (empty($activeLink)) {
      $this->setActiveLinksTrailViaRoutes();
    }
  }

  /**
   * Set active tabs.
   *
   * @todo Refactor after better understanding.
   */
  protected function setActiveTabsTrail() {
    // Try to get active tabs from tabs hierarchy.
    $activeSections = $this->getActiveSection();
    $currentRouteName = $this->toolbarRouteManager->getCurrentRoute();
    $tabs = $this->toolbarTabManager->getTabs();
    /** @var \Drupal\adminic_toolbar\ToolbarTab $tab */
    foreach ($tabs as $key => &$tab) {
      $tabUrl = $tab->getRawUrl();
      $tabRouteName = $tabUrl->getRouteName();
      if ($activeSections && $tab->getId() == $activeSections->getTab()) {
        $tab->setActive();
        $this->toolbarTabManager->addActiveTab($tab);
      }
      elseif ($tabRouteName == $currentRouteName) {
        $tab->setActive();
        $this->toolbarTabManager->addActiveTab($tab);
      }
    }

    // Set active tabs from routes.
    $activeTabs = $this->toolbarTabManager->getActiveTab();
    if (empty($activeTabs)) {
      $activeRoutes = $this->toolbarRouteManager->getActiveRoutes();
      $tabs = $this->toolbarTabManager->getTabs();
      foreach ($tabs as $tab) {
        $tabUrl = $tab->getRawUrl();
        $tabRouteName = $tabUrl->getRouteName();
        if (array_key_exists($tabRouteName, $activeRoutes)) {
          $tab->setActive();
          $this->toolbarTabManager->addActiveTab($tab);
        }
      }
    }
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
      if (empty($section[self::SECTION_ID])) {
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
   *
   * @throws \Exception
   */
  protected function setActiveLinksTrailViaRoutes() {
    $activeRoutes = $this->toolbarRouteManager->getActiveRoutes();
    $links = $this->toolbarLinkManager->getLinks();
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
   *
   * @throws \Exception
   */
  protected function setActiveLinksTrailViaConfig() {
    $currentRouteName = $this->toolbarRouteManager->getCurrentRoute();
    $links = $this->toolbarLinkManager->getLinks();
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
    $this->toolbarLinkManager->addActiveLink($link);
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
   *
   * @throws \Exception
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
