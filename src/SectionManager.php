<?php
/**
 * Created by PhpStorm.
 * User: jakubhnilicka
 * Date: 07.02.18
 * Time: 20:19
 */

namespace Drupal\adminic_toolbar;

use Drupal\Core\Extension\ModuleHandler;

class SectionManager {

  /**
   * @var \Drupal\adminic_toolbar\DiscoveryManager
   */
  private $discoveryManager;

  /**
   * @var \Drupal\adminic_toolbar\RouteManager
   */
  private $routeManager;

  /**
   * @var \Drupal\adminic_toolbar\LinkManager
   */
  private $linkManager;

  /**
   * @var \Drupal\adminic_toolbar\TabManager
   */
  private $tabManager;

  /**
   * @var array
   */
  private $sections = [];

  /**
   * @var array
   */
  private $activeSections = [];

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * @var \Drupal\adminic_toolbar\ToolbarWidgetPluginManager
   */
  private $toolbarWidgetPluginManager;

  /**
   * SectionManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   * @param \Drupal\adminic_toolbar\RouteManager $routeManager
   * @param \Drupal\adminic_toolbar\LinkManager $linkManager
   * @param \Drupal\adminic_toolbar\TabManager $tabManager
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   * @param \Drupal\adminic_toolbar\ToolbarWidgetPluginManager $toolbarWidgetPluginManager
   */
  public function __construct(
    DiscoveryManager $discoveryManager,
    RouteManager $routeManager,
    LinkManager $linkManager,
    TabManager $tabManager,
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
   */
  public function getPrimarySections(): array {
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
   */
  public function getSections() {
    if (empty($this->sections)) {
      $this->parseSections();
    }

    return $this->sections;
  }

  /**
   * Get all defined sections from all config files.
   */
  protected function parseSections() {
    $this->setActiveLinks();
    $config = $this->discoveryManager->getConfig();
    $activeLink = $this->linkManager->getActiveLink();

    $weight = 0;
    $configSections = [];
    foreach ($config as $configFile) {
      if (isset($configFile['widgets'])) {
        foreach ($configFile['widgets'] as $section) {
          $section['weight'] = isset($section['weight']) ? $section['weight'] : $weight;
          $section['set'] = isset($section['set']) ? $section['set'] : 'default';
          $key = $section['id'];
          $configSections[$key] = $section;
          $weight++;
        }
      }
    }
    uasort($configSections, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    $this->moduleHandler->alter('toolbar_config_sections', $configSections);

    foreach ($configSections as $section) {
      if ($section['set'] == $this->discoveryManager->getActiveSet()) {
        $id = $section['id'];
        $title = isset($section['title']) ? $section['title'] : NULL;
        $tab = isset($section['tab']) ? $section['tab'] : NULL;
        $disabled = isset($section['disabled']) ? $section['disabled'] : FALSE;
        $type = isset($section['type']) ? $section['type'] : NULL;
        $newSection = new Section($id, $title, $tab, $disabled, $type);
        $this->addSection($newSection);
        if ($activeLink && $id == $activeLink->getWidget()) {
          $this->addActiveSection($newSection);
        }
      }
    }

    $this->setActiveTabs();
  }

  /**
   * Set active links.
   */
  protected function setActiveLinks() {
    /** @var \Drupal\adminic_toolbar\Link $link */
    // Try to select active links from config links hiearchy.
    $currentRouteName = $this->routeManager->getCurrentRoute();
    $links = $this->linkManager->getLinks();
    foreach ($links as $key => &$link) {
      $url = $link->getRawUrl();
      $linkRouteName = $url->getRouteName();
      if ($linkRouteName == $currentRouteName) {
        $link->setActive();
        $this->linkManager->addActiveLink($link);
      }
    }

    // If active links are empty, select active links from routes.
    $activeLinks = $this->linkManager->getActiveLink();
    if (empty($activeLinks)) {
      $activeRoutes = $this->routeManager->getActiveRoutes();
      $links = $this->linkManager->getLinks();
      foreach ($links as &$link) {
        $url = $link->getRawUrl();
        $linkRouteName = $url->getRouteName();
        if (array_key_exists($linkRouteName, $activeRoutes)) {
          $link->setActive();
          $this->linkManager->addActiveLink($link);
        }
      }
    }
  }

  /**
   * Add section.
   *
   * @param \Drupal\adminic_toolbar\Section $section
   */
  public function addSection(Section $section) {
    $key = $this->getSectionKey($section);
    $this->sections[$key] = $section;
    // Remove section if exists and is disabled
    if (isset($this->sections[$key]) && $section->isDisabled()) {
      unset($this->sections[$key]);
    }
  }

  protected function getSectionKey(Section $section) {
    return $section->getId();
  }

  /**
   * Add active section.
   *
   * @param \Drupal\adminic_toolbar\Section $section
   */
  public function addActiveSection(Section $section) {
    $this->activeSections[] = $section;
  }

  /**
   * Set active tabs.
   */
  protected function setActiveTabs() {
    // Try to get active tabs from tybs hiearchy.
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
   * @return \Drupal\adminic_toolbar\Section|NULL
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
   *   Retrun renderable array or NULL.
   */
  public function getPrimarySection(Section $section) {
    $tabs = $this->tabManager->getTabs();
    $sectionId = $section->getId();

    $sectionValidTabs = array_filter(
      $tabs, function ($tab) use ($sectionId) {
      /** @var \Drupal\adminic_toolbar\Tab $tab */
      return $tab->getSection() == $sectionId;
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
   *
   * @return array
   */
  protected function getSecondarySectionsByTab(Tab $tab) {
    $sections = $this->getSections();

    /** @var \Drupal\adminic_toolbar\Tab $tab */
    $secondarySections = array_filter(
      $sections, function ($section) use ($tab) {
      /** @var \Drupal\adminic_toolbar\Section $section */
      $sectionTab = $section->getTab();
      return !empty($sectionTab) && $sectionTab == $tab->getId();
    }
    );

    if (empty($secondarySections)) {
      return NULL;
    }

    $renderedSections = [];
    foreach ($secondarySections as $key => $secondarySection) {
      $section = $this->getSecondarySection($secondarySection);
      if ($section != NULL) {
        $renderedSections[$key] = $section;
      }
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
   *   Retrun renderable array or null.
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

}
