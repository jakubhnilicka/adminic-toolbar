<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * Toolbar.php.
 */

use Drupal\Core\Config\Config;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;

/**
 * Class Toolbar.
 *
 * @package Drupal\adminic_toolbar
 */
class Toolbar {

  /**
   * Defines the default configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $systemSite;

  /**
   * Default object for current_route_match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * A proxied implementation of AccountInterface.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  private $currentUser;

  /**
   * Tabs manager.
   *
   * @var \Drupal\adminic_toolbar\TabsManager
   */
  private $tabsManager;

  /**
   * Links manager.
   *
   * @var \Drupal\adminic_toolbar\LinksManager
   */
  private $linksManager;

  /**
   * Sections manager.
   *
   * @var \Drupal\adminic_toolbar\SectionsManager
   */
  private $sectionsManager;

  /**
   * Toolbar widget plugin manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarWidgetPluginManager
   */
  private $toolbarWidgetPluginManager;

  /**
   * Toolbar constructor.
   *
   * @param \Drupal\Core\Config\Config $systemSite
   *   Defines the default configuration object.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Default object for current_route_match service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   A proxied implementation of AccountInterface.
   * @param \Drupal\adminic_toolbar\ToolbarWidgetPluginManager $toolbarWidgetPluginManager
   *   Toolbar widget plugin manager.
   * @param \Drupal\adminic_toolbar\TabsManager $tabsManager
   *   Tabs manager.
   * @param \Drupal\adminic_toolbar\LinksManager $linksManager
   *   Links manager.
   * @param \Drupal\adminic_toolbar\SectionsManager $sectionsManager
   *   Sections manager.
   */
  public function __construct(
    Config $systemSite,
    CurrentRouteMatch $currentRouteMatch,
    AccountProxy $currentUser,
    ToolbarWidgetPluginManager $toolbarWidgetPluginManager,
    TabsManager $tabsManager,
    LinksManager $linksManager,
    SectionsManager $sectionsManager) {
    $this->currentUser = $currentUser;
    $this->tabsManager = $tabsManager;
    $this->linksManager = $linksManager;
    $this->sectionsManager = $sectionsManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->systemSite = $systemSite;
    $this->toolbarWidgetPluginManager = $toolbarWidgetPluginManager;
  }

  /**
   * Get render array for primary toolbar.
   *
   * @return array|null
   *   Retrun renderable array or null if empty.
   */
  public function getPrimaryToolbar() {
    if (!$this->userCanAccessToolbar()) {
      return NULL;
    }

    $primarySections = $this->sectionsManager->getPrimarySections();
    $widgets = [];

    /** @var \Drupal\adminic_toolbar\Section $section */
    foreach ($primarySections as $section) {
      if ($section->hasType()) {
        $type = $section->getType();
        $widget = $this->toolbarWidgetPluginManager->createInstance($type);
        $widgets[] = $widget->getRenderArray();
      }
      else {
        $widgets[] = $this->sectionsManager->getPrimarySection($section);
      }
    }

    // Append user account to primary toolbar.
    $userAccount = $this->toolbarWidgetPluginManager->createInstance('user_account')->getRenderArray();

    $header = [
      '#theme' => 'toolbar_header',
      '#title' => t('Drupal'),
      '#title_link' => '<front>',
    ];

    if ($widgets) {
      return [
        '#theme' => 'toolbar_primary',
        '#header' => $header,
        '#title' => 'Drupal',
        '#widgets' => $widgets,
        '#user_account' => $userAccount,
        '#access' => $this->userCanAccessToolbar(),
        '#cache' => [
          'keys' => ['toolbar_primary'],
          'contexts' => ['user.permissions', 'url.path'],
        ],
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

    $secondaryWrappers = $this->sectionsManager->getSecondarySectionWrappers();

    /** @var \Drupal\adminic_toolbar\Tab $activeTab */
    $wrappers = [];

    foreach ($secondaryWrappers as $key => $wrapper) {
      $active = FALSE;
      if ($wrapper['sections']) {
        $header = [
          '#theme' => 'toolbar_header',
          '#title' => $wrapper['title'],
          '#title_link' => $wrapper['route'],
          '#close' => TRUE,
        ];

        $wrappers[] = [
          '#theme' => 'toolbar_secondary_wrapper',
          '#header' => $header,
          '#sections' => $wrapper['sections'],
          '#active' => $active,
          '#id' => $key,
          '#access' => $this->userCanAccessToolbar(),
        ];
      }
    }

    if (!empty($wrappers)) {
      return [
        '#theme' => 'toolbar_secondary',
        '#wrappers' => $wrappers,
        '#cache' => [
          'keys' => ['toolbar_secondary'],
          'contexts' => ['user.permissions', 'url.path'],
        ],
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
      '#markup' => "- {section: 'default', route: '" . $current_route_name . "'}",
    ];

    if ($adminic_toolbar_top) {
      return [
        '#theme' => 'toolbar_top',
        '#info' => $adminic_toolbar_top,
        '#access' => $this->userCanAccessToolbar(),
        '#cache' => [
          'keys' => ['toolbar_top'],
          'contexts' => ['user.permissions', 'url.path'],
        ],
      ];
    }

    return NULL;
  }

}
