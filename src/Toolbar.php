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
   * @var \Drupal\adminic_toolbar\ToolbarPrimarySectionTabsManager
   */
  private $tabsManager;

  /**
   * Links manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarSecondarySectionLinksManager
   */
  private $linksManager;

  /**
   * Primary sections manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarPrimarySectionsManager
   */
  private $primarySectionsManager;

  /**
   * Secondary sections manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarSecondarySectionsManager
   */
  private $secondarySectionsManager;

  /**
   * Toolbar widget plugin manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarPluginManager
   */
  private $toolbarPluginManager;

  private $toolbarConfiguration;

  /**
   * Toolbar constructor.
   *
   * @param \Drupal\Core\Config\Config $systemSite
   *   Defines the default configuration object.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Default object for current_route_match service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   A proxied implementation of AccountInterface.
   * @param \Drupal\adminic_toolbar\ToolbarPluginManager $toolbarPluginManager
   *   Toolbar widget plugin manager.
   * @param \Drupal\adminic_toolbar\ToolbarPrimarySectionTabsManager $tabsManager
   *   Tabs manager.
   * @param \Drupal\adminic_toolbar\ToolbarSecondarySectionLinksManager $linksManager
   *   Links manager.
   * @param \Drupal\adminic_toolbar\ToolbarPrimarySectionsManager $primarySectionsManager
   *   Primary sections manager.
   * @param \Drupal\adminic_toolbar\ToolbarSecondarySectionsManager $secondarySectionsManager
   *   Secondary sections manager.
   * @param \Drupal\Core\Config\Config $toolbarConfiguration
   *   Toolbar Configuration.
   */
  public function __construct(
    Config $systemSite,
    CurrentRouteMatch $currentRouteMatch,
    AccountProxy $currentUser,
    ToolbarPluginManager $toolbarPluginManager,
    ToolbarPrimarySectionTabsManager $tabsManager,
    ToolbarSecondarySectionLinksManager $linksManager,
    ToolbarPrimarySectionsManager $primarySectionsManager,
    ToolbarSecondarySectionsManager $secondarySectionsManager,
    Config $toolbarConfiguration) {
    $this->systemSite = $systemSite;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->currentUser = $currentUser;
    $this->toolbarPluginManager = $toolbarPluginManager;
    $this->tabsManager = $tabsManager;
    $this->linksManager = $linksManager;
    $this->primarySectionsManager = $primarySectionsManager;
    $this->secondarySectionsManager = $secondarySectionsManager;
    $this->toolbarConfiguration = $toolbarConfiguration;
  }

  /**
   * Generate active trail.
   *
   * @throws \Exception
   */
  protected function generateActiveTrails() {
    $this->linksManager->getLinks();
    $this->secondarySectionsManager->getSecondarySections();
  }

  /**
   * Get render array for primary toolbar.
   *
   * @return array|null
   *   Retrun renderable array or null if empty.
   *
   * @throws \Exception
   */
  public function getPrimaryToolbar() {
    if (!$this->userCanAccessToolbar()) {
      return NULL;
    }

    $activeTheme = \Drupal::theme()->getActiveTheme();
    $activeThemeName = $activeTheme->getName();
    $adminic_toolbar_theme = $this->toolbarConfiguration->get('adminic_toolbar_theme');
    $theme = $adminic_toolbar_theme[$activeThemeName] ?: 'adminic_toolbar/adminic_toolbar.theme.default';

    $this->generateActiveTrails();
    $primarySections = $this->primarySectionsManager->getPrimarySections();
    $widgets = [];

    /** @var \Drupal\adminic_toolbar\ToolbarPrimarySection $section */
    foreach ($primarySections as $section) {
      if ($section->hasType()) {
        $type = $section->getType();
        $widget = $this->toolbarPluginManager->createInstance($type);
        $widgets[] = $widget->getRenderArray();
      }
      else {
        $widgets[] = $this->primarySectionsManager->getPrimarySection($section);
      }
    }

    // Append user account to primary toolbar.
    $userAccount = $this->toolbarPluginManager->createInstance('toobar_user_account')->getRenderArray();
    $toolbarConfiguration = $this->toolbarPluginManager->createInstance('toolbar_configuration')->getRenderArray();

    $header = [
      '#theme' => 'toolbar_primary_header',
      '#title' => t('Drupal'),
      '#title_link' => '/',
    ];

    if ($widgets) {
      $activeTab = $this->tabsManager->getActiveTab();
      $activeLink = $this->linksManager->getActiveLinkUrl();
      $build = [];
      $build['toolbar_primary'] = [
        '#theme' => 'toolbar_primary',
        '#header' => $header,
        '#title' => 'Drupal',
        '#widgets' => $widgets,
        '#toolbar_configuration' => $toolbarConfiguration,
        '#user_account' => $userAccount,
        '#access' => $this->userCanAccessToolbar(),
        '#cache' => [
          'keys' => ['adminic_toolbar_primary'],
          'contexts' => ['user.permissions'],
        ],
        '#attached' => [
          'library' => [
            $theme,
          ],
        ],
      ];

      $routeName = $this->currentRouteMatch->getRouteName();
      $routeParameters = $this->currentRouteMatch->getParameters();
      $routeParams = [];
      $params = $routeParameters->all();
      foreach ($params as $key => $parameter) {
        $routeParams[] = $key;
      }

      $build['drupal_settings'] = [
        '#markup' => '',
        '#attached' => [
          'drupalSettings' => [
            'adminic_toolbar' => [
              'active_tab' => $activeTab,
              'active_link' => $activeLink,
              'route_name' => $routeName,
              'route_parameters' => $routeParams,
            ],
          ],
        ],
      ];

      return $build;
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
    $userHasPermissions = $this->currentUser->hasPermission('can use adminic toolbar');
    return $userHasPermissions;
  }

  /**
   * Get render array for secondary toolbar.
   *
   * @return array|null
   *   Retrun renderable array or null.
   *
   * @throws \Exception
   */
  public function getSecondaryToolbar() {
    if (!$this->userCanAccessToolbar()) {
      return NULL;
    }

    $secondaryWrappers = $this->secondarySectionsManager->getSecondarySectionWrappers();

    /** @var \Drupal\adminic_toolbar\ToolbarPrimarySectionTab $activeTab */
    $wrappers = [];

    foreach ($secondaryWrappers as $key => $wrapper) {
      $active = FALSE;
      if ($wrapper['sections']) {
        $header = [
          '#theme' => 'toolbar_secondary_header',
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
          'keys' => ['adminic_toolbar_secondary'],
          'contexts' => ['user.permissions'],
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
