<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;

class AdminicToolbar {

  /**
   * @var \Drupal\user\Plugin\views\argument_default\CurrentUser
   */
  private $currentUser;

  /**
   * @var \Drupal\adminic_toolbar\TabManager
   */
  private $tabManager;

  /**
   * @var \Drupal\adminic_toolbar\LinkManager
   */
  private $linkManager;

  /**
   * @var \Drupal\adminic_toolbar\SectionManager
   */
  private $sectionManager;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * AdminicToolbar constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   * @param \Drupal\adminic_toolbar\TabManager $tabManager
   * @param \Drupal\adminic_toolbar\LinkManager $linkManager
   * @param \Drupal\adminic_toolbar\SectionManager $sectionManager
   */
  public function __construct(
    CurrentRouteMatch $currentRouteMatch,
    AccountProxy $currentUser,
    TabManager $tabManager,
    LinkManager $linkManager,
    SectionManager $sectionManager) {
    $this->currentUser = $currentUser;
    $this->tabManager = $tabManager;
    $this->linkManager = $linkManager;
    $this->sectionManager = $sectionManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * Get render array for primary toolbar.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  public function getPrimaryToolbar() {
    if (!$this->userCanAccessToolbar()) {
      return NULL;
    }
    $primarySections = $this->sectionManager->getPrimarySections();

    $sections = [];
    foreach ($primarySections as $section) {
      if ($section->hasCallback()) {
        $callback = $section->getCallback();
        $return = call_user_func($callback);
        $sections[] = $return;
      }
      else {
        $sections[] = $this->getPrimarySection($section);
      }
    }

    if ($sections) {
      return [
        '#theme' => 'adminic_toolbar_primary',
        '#title' => 'Drupal',
        '#sections' => $sections,
      ];
    }
    return NULL;
  }

  /**
   * Get render array for secondary toolbar.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  public function getSecondaryToolbar() {
    if (!$this->userCanAccessToolbar()) {
      return NULL;
    }
    $secondaryWrappers = $this->getSecondaryWrappers();
    $activeTab = $this->tabManager->getActiveTab();
    $wrappers = [];
    foreach ($secondaryWrappers as $key => $wrapper) {
      $active = FALSE;
      if (!empty($activeTab)) {
        $active = ($key == $activeTab->getId());
      }
      if ($wrapper['sections']) {
        $wrappers[] = [
          '#theme' => 'adminic_toolbar_secondary_wrapper',
          '#title' => $wrapper['title'],
          '#title_link' => $wrapper['route'],
          '#sections' => $wrapper['sections'],
          '#active' => $active,
          '#id' => $key,
        ];
      }
    }

    if (!empty($wrappers)) {
      return [
        '#theme' => 'adminic_toolbar_secondary',
        '#wrappers' => $wrappers,
      ];
    }

    return NULL;
  }

  /**
   * Get render array for top toolbar.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  public function getTopToolbar() {
    if (!$this->userCanAccessToolbar()) {
      return NULL;
    }
    $current_route_name = $this->currentRouteMatch->getRouteName();

    $adminic_toolbar_top = [];
    // TODO: move config to constructor.
    $config = \Drupal::config('system.site');

    $adminic_toolbar_top[] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $config->get('name'),
      '#attributes' => [
        'class' => [
          'site-title',
        ],
      ],
    ];

    $adminic_toolbar_top[] = [
      '#type' => 'markup',
      '#markup' => 'Route: ' . $current_route_name,
    ];

    if ($adminic_toolbar_top) {
      return [
        '#theme' => 'adminic_toolbar_top',
        '#info' => $adminic_toolbar_top,
      ];
    }

    return NULL;
  }

  protected function getSecondaryWrappers() {
    $tabs = $this->tabManager->getTabs();
    $secondaryWrappers = [];
    foreach ($tabs as $tab) {
      $sections = $this->getTabSections($tab);

      $secondaryWrappers[$tab->getId()] = [
        'title' => $tab->getTitle(),
        'route' => $tab->getRoute(),
        'sections' => $sections
      ];
    }

    return $secondaryWrappers;
  }

  protected function getTabSections($tab): array {
    $sections = $this->sectionManager->getSections();

    $secondarySections = array_filter(
      $sections, function ($section) use ($tab) {
      $sectionTab = $section->getTab();
        return !empty($sectionTab) && $sectionTab == $tab->getId();
      }
    );

    $renderedSections = [];
    foreach ($secondarySections as $key => $secondarySection) {
      $ss = $this->getSecondarySection($secondarySection);
      if ($ss != NULL) {
        $renderedSections[$key] = $ss;
      }
    }

    return $renderedSections;
  }

  /**
   * Get renderable array for primary section.
   *
   * @param \Drupal\adminic_toolbar\Section $section
   *   Section.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  protected function getPrimarySection(Section $section) {
    $tabs = $this->tabManager->getTabs();
    $sectionId = $section->getId();

    $sectionValidTabs = array_filter(
      $tabs, function ($tab) use ($sectionId) {
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
        return $link->getSection() == $sectionId;
      }
    );

    if(empty($sectionValidLinks)) {
      return NULL;
    }

    /** @var \Drupal\adminic_toolbar\Link $link */
    $sectionLinks = [];
    foreach ($sectionValidLinks as $link) {
      $sectionLinks[] = $link->getRenderArray();
    }
    $section->setLinks($sectionLinks);
    return $section->getRenderArray();
  }

  /**
   * Check if current user can access toolbar.
   *
   * @return bool
   *   Retrun true if user can access toolbar or false.
   */
  protected function userCanAccessToolbar() {
    return $this->currentUser->hasPermission('can use adminic toolbar');
  }

}
