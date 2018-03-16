<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * SectionsManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Exception;

/**
 * Class SectionsManager.
 *
 * @package Drupal\adminic_toolbar
 */
class SectionsManager {

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
   * Links manager.
   *
   * @var \Drupal\adminic_toolbar\LinksManager
   */
  private $linkManager;

  /**
   * Tabs manager.
   *
   * @var \Drupal\adminic_toolbar\TabsManager
   */
  private $tabManager;

  /**
   * Sections.
   *
   * @var array
   */
  private $sections = [];

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
   * @var \Drupal\adminic_toolbar\ToolbarWidgetPluginManager
   */
  private $toolbarWidgetPluginManager;

  /**
   * SectionsManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   *   Discovery manager.
   * @param \Drupal\adminic_toolbar\RouteManager $routeManager
   *   Route manager.
   * @param \Drupal\adminic_toolbar\LinksManager $linkManager
   *   Links manager.
   * @param \Drupal\adminic_toolbar\TabsManager $tabManager
   *   Tabs manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   * @param \Drupal\adminic_toolbar\ToolbarWidgetPluginManager $toolbarWidgetPluginManager
   *   Toolbar widget plugin manager.
   *
   * @todo Check names for $linkManager, $tabManager, when classes are LinksManager, TabsManager.
   */
  public function __construct(
    DiscoveryManager $discoveryManager,
    RouteManager $routeManager,
    LinksManager $linkManager,
    TabsManager $tabManager,
    ModuleHandler $moduleHandler,
    ToolbarWidgetPluginManager $toolbarWidgetPluginManager) {
    $this->discoveryManager = $discoveryManager;
    $this->linkManager = $linkManager;
    $this->tabManager = $tabManager;
    $this->routeManager = $routeManager;
    $this->moduleHandler = $moduleHandler;
    $this->toolbarWidgetPluginManager = $toolbarWidgetPluginManager;
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
    $sections = $this->getSections();

    $primarySections = array_filter(
      $sections, function ($section) {
        /** @var \Drupal\adminic_toolbar\Section $section */
        return $section->getTab() == NULL;
      }
    );

    return $primarySections;
  }

  /**
   * Get sections.
   *
   * @return array
   *   Return array of sections.
   *
   * @throws \Exception
   */
  public function getSections() {
    if (empty($this->sections)) {
      $this->parseSections();
    }

    return $this->sections;
  }

  /**
   * Get all defined sections from all config files.
   *
   * @throws \Exception
   */
  protected function parseSections() {
    $this->setActiveLinks();
    $config = $this->discoveryManager->getConfig();

    $weight = 0;
    $configSections = [];
    foreach ($config as $configFile) {
      if (isset($configFile['widgets'])) {
        foreach ($configFile['widgets'] as $section) {
          // If weight is empty set computed value.
          $section['weight'] = isset($section['weight']) ? $section['weight'] : $weight;
          // If set is empty set default set.
          $section['set'] = isset($section['set']) ? $section['set'] : 'default';
          // TODO: get key from method.
          $key = $section['id'];
          $configSections[$key] = $section;
          $weight++;
        }
      }
    }
    // Sort tabs by weight.
    uasort($configSections, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_config_sections', $configSections);

    // Add tabs.
    $this->addSections($configSections);
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
   * @param \Drupal\adminic_toolbar\Section $section
   *   Section.
   */
  public function addSection(Section $section) {
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
   * @param \Drupal\adminic_toolbar\Section $section
   *   Section.
   *
   * @return string
   *   Return section key.
   */
  protected function getSectionKey(Section $section) {
    return $section->getId();
  }

  /**
   * Add active section.
   *
   * @param \Drupal\adminic_toolbar\Section $section
   *   Section.
   */
  public function addActiveSection(Section $section) {
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
    /** @var \Drupal\adminic_toolbar\Tab $tab */
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
   * @return \Drupal\adminic_toolbar\Section|null
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
   * @param \Drupal\adminic_toolbar\Section $section
   *   Section.
   *
   * @return array|null
   *   Return renderable array or NULL.
   */
  public function getPrimarySection(Section $section) {
    $tabs = $this->tabManager->getTabs();
    $sectionId = $section->getId();

    $sectionValidTabs = array_filter(
      $tabs, function ($tab) use ($sectionId) {
        /** @var \Drupal\adminic_toolbar\Tab $tab */
        return $tab->getWidget() == $sectionId;
      }
    );

    $sectionTabs = [];
    /** @var \Drupal\adminic_toolbar\Tab $tab */
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
    /** @var \Drupal\adminic_toolbar\Tab $tab */
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
   * @param \Drupal\adminic_toolbar\Tab $tab
   *   Tab.
   *
   * @return array
   *   Return array of secondary sections for specified tab.
   *
   * @throws \Exception
   */
  protected function getSecondarySectionsByTab(Tab $tab) {
    $renderedSections = [];
    $sections = $this->getSections();

    /** @var \Drupal\adminic_toolbar\Tab $tab */
    $secondarySections = array_filter(
      $sections, function ($section) use ($tab) {
        /** @var \Drupal\adminic_toolbar\Section $section */
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
   * @param \Drupal\adminic_toolbar\Section $section
   *   Section.
   *
   * @return array|null
   *   Return renderable array or null.
   *
   * @throws \Exception
   */
  protected function getSecondarySection(Section $section) {
    if ($section->hasType()) {
      $type = $section->getType();
      $widget = $this->toolbarWidgetPluginManager->createInstance($type);
      return $widget->getRenderArray();
    }

    $links = $this->linkManager->getLinks();
    $sectionId = $section->getId();

    $sectionValidLinks = array_filter(
      $links, function ($link) use ($sectionId) {
        /** @var \Drupal\adminic_toolbar\Link $link */
        return $link->getWidget() == $sectionId;
      }
    );

    if (empty($sectionValidLinks)) {
      return NULL;
    }

    $sectionLinks = [];
    /** @var \Drupal\adminic_toolbar\Link $link */
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
   * Add sections.
   *
   * @param array $configSections
   *   Array of sections.
   */
  protected function addSections(array $configSections) {
    $activeLink = $this->linkManager->getActiveLink();

    foreach ($configSections as $section) {
      if ($section['set'] == $this->discoveryManager->getActiveSet()) {
        $this->validateSection($section);

        $id = $section['id'];
        $title = isset($section['title']) ? $section['title'] : '';
        $tab_id = isset($section['tab_id']) ? $section['tab_id'] : '';
        $disabled = isset($section['disabled']) ? $section['disabled'] : FALSE;
        $type = isset($section['type']) ? $section['type'] : '';
        $newSection = new Section($id, $title, $tab_id, $disabled, $type);
        $this->addSection($newSection);

        if ($activeLink && $id == $activeLink->getWidget()) {
          $this->addActiveSection($newSection);
        }
      }
    }

    $this->setActiveTabs();
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
      if (empty($section['id'])) {
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
      /** @var \Drupal\adminic_toolbar\Link $link */
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
      /** @var \Drupal\adminic_toolbar\Link $link */
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
   * @param \Drupal\adminic_toolbar\Link $link
   *   Link.
   */
  protected function activateLinkByLink(Link $link) {
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
