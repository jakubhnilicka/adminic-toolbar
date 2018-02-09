<?php
/**
 * Created by PhpStorm.
 * User: jakubhnilicka
 * Date: 07.02.18
 * Time: 20:19
 */

namespace Drupal\adminic_toolbar;

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
   * SectionManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   * @param \Drupal\adminic_toolbar\RouteManager $routeManager
   * @param \Drupal\adminic_toolbar\LinkManager $linkManager
   * @param \Drupal\adminic_toolbar\TabManager $tabManager
   */
  public function __construct(
    DiscoveryManager $discoveryManager,
    RouteManager $routeManager,
    LinkManager $linkManager,
    TabManager $tabManager) {
    $this->discoveryManager = $discoveryManager;
    $this->linkManager = $linkManager;
    $this->tabManager = $tabManager;
    $this->routeManager = $routeManager;

  }

  /**
   * Get all defined sections from all config files.
   */
  protected function parseSections() {
    $this->setActiveLinks();
    $config = $this->discoveryManager->getConfig();
    $activeLink = $this->linkManager->getActiveLink();

    foreach ($config as $configFile) {
      if ($configFile['set']['id'] == 'default' && isset($configFile['set']['sections'])) {
        foreach ($configFile['set']['sections'] as $section) {
          $id = $section['id'];
          $title = isset($section['title']) ? $section['title'] : NULL;
          $tab = isset($section['tab']) ? $section['tab'] : NULL;
          $disabled = isset($section['disabled']) ? $section['disabled'] : FALSE;
          $callback = isset($section['callback']) ? $section['callback'] : NULL;
          // TODO: Fix disabled override.
          // TODO: Implement weight sorting.
          if ($disabled == FALSE) {
            $newSection = new Section($id, $title, $tab, $callback);
            $this->addSection($newSection);
            if ($activeLink && $id == $activeLink->getSection()) {
              $this->addActiveSection($newSection);
            }
          }
        }
      }
    }

    $this->setActiveTabs();
  }

  /**
   * Add section.
   *
   * @param \Drupal\adminic_toolbar\Section $section
   */
  public function addSection(Section $section) {
    $this->sections[] = $section;
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
   * Set active tabs.
   */
  protected function setActiveTabs() {
    $activeSections = $this->getActiveSection();
    $currentRouteName = $this->routeManager->getCurrentRoute();
    $tabs = $this->tabManager->getTabs();
    /** @var \Drupal\adminic_toolbar\Tab $tab */
    foreach ($tabs as $key => &$tab) {
      if ($activeSections && $tab->getId() == $activeSections->getTab()) {
        $tab->setActive();
        $this->tabManager->addActiveTab($tab);
      }
      elseif ($tab->getRoute() == $currentRouteName) {
        $tab->setActive();
        $this->tabManager->addActiveTab($tab);
      }
    }
  }

  /**
   * Set active links.
   */
  protected function setActiveLinks() {
    $currentRouteName = $this->routeManager->getCurrentRoute();
    $links = $this->linkManager->getLinks();
    /** @var \Drupal\adminic_toolbar\Link $link */
    foreach ($links as $key => &$link) {
      if ($link->getRoute() == $currentRouteName) {
        $link->setActive();
        $this->linkManager->addActiveLink($link);
      }
    }
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
   * Get secondary sections by tab.
   *
   * @param \Drupal\adminic_toolbar\Tab $tab
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
    $links = $this->linkManager->getLinks();
    $sectionId = $section->getId();

    $sectionValidLinks = array_filter(
      $links, function ($link) use ($sectionId) {
        /** @var \Drupal\adminic_toolbar\Link $link */
        return $link->getSection() == $sectionId;
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
        'route' => $tab->getRoute(),
        'sections' => $sections
      ];
    }

    return $secondaryWrappers;
  }

}
