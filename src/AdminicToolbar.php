<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Config\Config;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;

class AdminicToolbar {

  /**
   * @var \Drupal\Core\Config\Config
   */
  private $systemSite;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

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
   * @var \Drupal\adminic_toolbar\ToolbarWidgetPluginManager
   */
  private $toolbarWidgetPluginManager;

  /**
   * AdminicToolbar constructor.
   *
   * @param \Drupal\Core\Config\Config $systemSite
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   * @param \Drupal\adminic_toolbar\ToolbarWidgetPluginManager $toolbarWidgetPluginManager
   * @param \Drupal\adminic_toolbar\TabManager $tabManager
   * @param \Drupal\adminic_toolbar\LinkManager $linkManager
   * @param \Drupal\adminic_toolbar\SectionManager $sectionManager
   */
  public function __construct(
    Config $systemSite,
    CurrentRouteMatch $currentRouteMatch,
    AccountProxy $currentUser,
    ToolbarWidgetPluginManager $toolbarWidgetPluginManager,
    TabManager $tabManager,
    LinkManager $linkManager,
    SectionManager $sectionManager) {
    $this->currentUser = $currentUser;
    $this->tabManager = $tabManager;
    $this->linkManager = $linkManager;
    $this->sectionManager = $sectionManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->systemSite = $systemSite;
    $this->toolbarWidgetPluginManager = $toolbarWidgetPluginManager;
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
    $widgets = [];

    /** @var \Drupal\adminic_toolbar\Section $section */
    foreach ($primarySections as $section) {
      if ($section->hasCallback()) {
        $widgetManager = $this->toolbarWidgetPluginManager;
        $callback = $section->getCallback();
        $widget = $this->toolbarWidgetPluginManager->createInstance($callback);
        $widgets[] = $widget->getRenderArray();
      }
      else {
        $widgets[] = $this->sectionManager->getPrimarySection($section);
      }
    }

    if ($widgets) {
      return [
        '#theme' => 'adminic_toolbar_primary',
        '#title' => 'Drupal',
        '#widgets' => $widgets,
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

    $secondaryWrappers = $this->sectionManager->getSecondarySectionWrappers();
    /** @var \Drupal\adminic_toolbar\Tab $activeTab */
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

    $adminic_toolbar_top[] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->systemSite->get('name'),
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
