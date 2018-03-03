<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Config\Config;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;

class Toolbar {

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
   * Toolbar constructor.
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
      if ($section->hasType()) {
        $type = $section->getType();
        $widget = $this->toolbarWidgetPluginManager->createInstance($type);
        $widgets[] = $widget->getRenderArray();
      }
      else {
        $widgets[] = $this->sectionManager->getPrimarySection($section);
      }
    }

    $userAccount = $this->toolbarWidgetPluginManager->createInstance('user_account')
      ->getRenderArray();

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
          //'keys' => ['toolbar_primary'],
          //'contexts' => ['user.permissions', 'url.path'],
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
        $header = [
          '#theme' => 'toolbar_header',
          '#title' => $wrapper['title'],
          '#title_link' => $wrapper['route'],
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
