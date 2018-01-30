<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountProxy;

class AdminicToolbar {

  /**
   * @var \Drupal\Core\Routing\RouteProvider
   */
  private $routeProvider;

  /**
   * @var array
   */
  private $config;

  /**
   * @var array
   */
  private $activeTab = NULL;

  /**
   * @var array
   */
  private $routes;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * @var \Drupal\Core\Access\AccessManager
   */
  private $accessManager;

  /**
   * @var \Drupal\user\Plugin\views\argument_default\CurrentUser
   */
  private $currentUser;

  /**
   * AdminicToolbar constructor.
   *
   * @param \Drupal\Core\Routing\RouteProvider $routeProvider
   *   Route Provider.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Current Route Match.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module Handler.
   * @param \Drupal\Core\Access\AccessManager $accessManager
   *   Access Manager.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   */
  public function __construct(
    RouteProvider $routeProvider,
    CurrentRouteMatch $currentRouteMatch,
    ModuleHandlerInterface $moduleHandler,
    AccessManager $accessManager,
    AccountProxy $currentUser) {
    $this->routeProvider = $routeProvider;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->moduleHandler = $moduleHandler;
    $this->accessManager = $accessManager;
    $this->currentUser = $currentUser;
    $this->config = $this->loadConfig();
    $this->routes = $this->getAvailableRoutes();
    $this->activeTab = $this->setActiveTab();
  }

  /**
   * Get render array for primary toolbar.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  public function getToolbarPrimary() {
    $primarySections = $this->getPrimarySections();

    $sections = [];
    foreach ($primarySections as $section) {
      $sections[] = $this->getPrimarySection($section);
    }

    if ($sections) {
      return [
        '#theme' => 'adminic_toolbar_primary',
        '#title' => 'Drupal', // TODO: Enable to modify custom title.
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
  public function getToolbarSecondary() {
    $secondarySections = $this->getSecondarySections();

    $sections = [];
    foreach ($secondarySections as $section) {
      $secondarySection = $this->getSecondarySection($section);
      if(!empty($secondarySection)) {
        $sections[] = $secondarySection;
      }
    }

    if ($sections) {
      $activeLink = $this->getActiveLink();
      $activeSection = $this->getActiveSection($activeLink);
      $activeTab = $this->getActiveTab($activeSection);

      return [
        '#theme' => 'adminic_toolbar_secondary',
        '#title' => $this->getTabTitle($activeTab),
        '#title_link' => $this->getTabRoute($activeTab),
        '#sections' => $sections,
      ];
    }
  }

  /**
   * Get render array for top toolbar.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  public function getToolbarTop() {

    $current_route_name = $this->currentRouteMatch->getRouteName();

    $adminic_toolbar_top = [];
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

  /**
   * Load all confuguration files for toolbar and convert them to array.
   *
   * @return array
   *   Configuration parsed from yaml files.
   */
  protected function loadConfig() {
    $discovery = new YamlDiscovery('toolbar', $this->moduleHandler->getModuleDirectories());
    $toolbarData = $discovery->findAll();
    return $toolbarData;
  }

  /**
   * Get all defined tabs from all config files.
   *
   * @return array
   *   Array of tabs.
   */
  protected function getTabs() {
    // Get main sections
    $tabs = [];
    $config = $this->config;

    foreach ($config as $configFile) {
      if (isset($configFile['tabs'])) {
        foreach ($configFile['tabs'] as $tab) {
          $id = $tab['id'];
          $section = isset($tab['section']) ? $tab['section'] : NULL;
          $route = $tab['route'];
          $title = isset($tab['title']) ? $tab['title'] : NULL;
          $disabled = isset($tab['disabled']) ? $tab['disabled'] : FALSE;
          if ($disabled == FALSE) {
            $tabs[$id] = [
              'id' => $id,
              'section' => $section,
              'route' => $route,
              'title' => $title,
            ];
          }
        }
      }
    }
    return $tabs;
  }

  /**
   * Get all defined sections from all config files.
   *
   * @return array
   *   Array of sections.
   */
  protected function getSections(): array {
    $sections = [];
    $config = $this->config;

    foreach ($config as $configFile) {
      if (isset($configFile['sections'])) {
        foreach ($configFile['sections'] as $section) {
          $id = $section['id'];
          $title = isset($section['title']) ? $section['title'] : NULL;
          $tab = isset($section['tab']) ? $section['tab'] : NULL;
          $disabled = isset($section['disabled']) ? $section['disabled'] : FALSE;
          if ($disabled == FALSE) {
            $sections[$id] = ['id' => $id, 'tab' => $tab, 'title' => $title];
          }
        }
      }
    }
    return $sections;
  }

  /**
   * Get all defined links from all config files.
   *
   * @return array
   *   Array of links.
   */
  protected function getLinks(): array {
    // Get main sections
    $links = [];
    $config = $this->config;

    foreach ($config as $configFile) {
      if (isset($configFile['links'])) {
        foreach ($configFile['links'] as $link) {
          $section = $link['section'];
          $route = $link['route'];
          $title = isset($link['title']) ? $link['title'] : NULL;
          $disabled = isset($link['disabled']) ? $link['disabled'] : FALSE;
          if ($disabled == FALSE) {
            $key = sprintf('%s.%s', $section, $route);
            $links[$key] = [
              'section' => $section,
              'route' => $route,
              'title' => $title,
            ];
          }
        }
      }
    }
    return $links;
  }

  /**
   * Tabs
   */
  /**
   * Get renderable array for tab.
   *
   * @param $tab
   *   Tab.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  protected function getTab($tab) {
    $routeName = $this->getLinkRoute($tab);
    if ($this->isRouteValid($routeName) && $this->isRouteAccessible($routeName) && !$this->isTabDisabled($tab)) {
      return [
        '#theme' => 'adminic_toolbar_section_tab',
        '#title' => $this->getTabTitle($tab),
        '#route' => $this->getTabRoute($tab),
        '#active' => $this->isTabActive($tab),
      ];
    }
    return NULL;
  }

  /**
   * Get tab title.
   *
   * @param $tab
   *   Tab.
   *
   * @return mixed
   *   Return tab title.
   */
  protected function getTabTitle($tab) {
    if (empty($tab['title'])) {
      return $this->routes[$this->getTabRoute($tab)];
    }
    return $tab['title'];
  }

  /**
   * Get tab route.
   *
   * @param $tab
   *   Tab.
   *
   * @return mixed
   *   Return tab route.
   */
  protected function getTabRoute($tab) {
    return $tab['route'];
  }

  /**
   * Check if tab is disabled.
   *
   * @param $tab
   *   Tab.
   *
   * @return bool
   *   True if route is disabled or flase.
   */
  protected function isTabDisabled($tab) {
    // Autogenerate titles
    if (empty($tab['disabled'])) {
      return FALSE;
    }
    $disabled = $tab['disabled'] == TRUE ? TRUE : FALSE;
    return $disabled;
  }

  /**
   * Check if tab is active.
   *
   * @param $tab
   *   Tab.
   *
   * @return boolean
   *   Return true if tab is active or false.
   */
  protected function isTabActive($tab) {
    if (empty($this->activeTab)) {
      return FALSE;
    }
    if ($this->activeTab['id'] == $tab['id']) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get active tab defined by active session.
   *
   * @param $activeSection
   *   Active section.
   *
   * @return array
   *   Return first active tab.
   */
  protected function getActiveTab($activeSection) {
    $tabs = $this->getTabs();
    $current_route_name = $this->currentRouteMatch->getRouteName();

    if ($activeSection == FALSE) {
      $activeTabs = array_filter(
        $tabs, function ($tab) use ($current_route_name) {
          return $tab['route'] == $current_route_name;
        }
      );
    }
    else {
      $activeTabs = array_filter(
        $tabs, function ($tab) use ($activeSection) {
          return $tab['id'] == $activeSection['tab'];
        }
      );
    }

    $activeTab = reset($activeTabs);
    return $activeTab;
  }

  protected function setActiveTab() {
    $activeLink = $this->getActiveLink();
    $activeSection = $this->getActiveSection($activeLink);
    return $this->getActiveTab($activeSection);
  }

  /**
   * Sections
   */
  /**
   * Get sections defined for primary toolbar.
   *
   * @return array
   *   Array of sections.
   */
  protected function getPrimarySections(): array {
    $sections = $this->getSections();
    $primarySections = array_filter(
      $sections, function ($section) {
        return $section['tab'] == NULL;
      }
    );
    return $primarySections;
  }

  /**
   * Get sections defined for secondary toolbar.
   *
   * @return array
   *   Array of sections.
   */
  protected function getSecondarySections(): array {
    $sections = $this->getSections();

    $secondarySections = [];
    if (!empty($this->activeTab)) {
      $activeTab = $this->activeTab;
      $secondarySections = array_filter(
        $sections, function ($section) use ($activeTab) {
          $tab = $section['tab'];
          return !empty($tab) && $tab == $activeTab['id'];
        }
      );
    }

    return $secondarySections;
  }

  /**
   * Get renderable array for primary section.
   *
   * @param array $section
   *   Section.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  protected function getPrimarySection(array $section) {
    $tabs = $this->getTabs();
    $sectionId = $section['id'];
    $sectionValidLinks = array_filter(
      $tabs, function ($tab) use ($sectionId) {
        return $tab['section'] == $sectionId;
      }
    );
    $sectionLinks = [];
    foreach ($sectionValidLinks as $link) {
      $sectionLinks[] = $this->getTab($link);
    }

    return [
      '#theme' => 'adminic_toolbar_section',
      '#title' => $this->getSectionTitle($section),
      '#links' => $sectionLinks,
    ];
  }

  /**
   * Get renderable array for secondary section.
   *
   * @param array $section
   *   Section.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  protected function getSecondarySection(array $section) {
    $links = $this->getLinks();
    $sectionId = $section['id'];
    $sectionValidLinks = array_filter(
      $links, function ($link) use ($sectionId) {
        return $link['section'] == $sectionId;
      }
    );
    $sectionLinks = [];
    foreach ($sectionValidLinks as $link) {
      $sectionLinks[] = $this->getLink($link);
    }
    if (!empty($sectionLinks)) {
      return [
        '#theme' => 'adminic_toolbar_section',
        '#title' => $this->getSectionTitle($section),
        '#links' => $sectionLinks,
      ];
    }
    //return NULL;
  }

  /**
   * Get active section defined by active link.
   *
   * @param $activeLink
   *   Active link.
   *
   * @return array
   *   Return first active section.
   */
  protected function getActiveSection($activeLink) {
    $sections = $this->getSections();
    // Active links
    $activeSections = array_filter(
      $sections, function ($section) use ($activeLink) {
      return $section['id'] == $activeLink['section'];
    }
    );

    $activeSection = reset($activeSections);
    return $activeSection;
  }

  /**
   * Get section title.
   *
   * @param array $section
   *   Section.
   *
   * @return string|null
   *   Retrun section title or null.
   */
  protected function getSectionTitle(array $section) {
    $sectionTitle = isset($section['title']) ? $section['title'] : NULL;
    return $sectionTitle;
  }

  /**
   * Get section type.
   *
   * @param array $section
   *   Section.
   *
   * @return string
   *   Retrun section type.
   */
  protected function getSectionType(array $section) {
    return 'adminic_toolbar_section';
  }

  /**
   * Links
   */
  /**
   * Get active link defined by current route.
   *
   * @return array
   *   Return first active link.
   */
  protected function getActiveLink() {
    $current_route_name = $this->currentRouteMatch->getRouteName();
    $links = $this->getLinks();
    $activeLinks = array_filter(
      $links, function ($link) use ($current_route_name) {
        return $link['route'] == $current_route_name;
      }
    );

    $activeLink = reset($activeLinks);
    return $activeLink;
  }

  /**
   * Get renderable array for link.
   *
   * @param $link
   *   Link.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  protected function getLink($link) {
    $routeName = $this->getLinkRoute($link);
    if ($this->isRouteValid($routeName) && $this->isRouteAccessible($routeName) && !$this->isLinkDisabled($link)) {
      return [
        '#theme' => 'adminic_toolbar_section_link',
        '#title' => $this->getLinkTitle($link),
        '#route' => $this->getLinkRoute($link),
        '#active' => $this->isLinkActive($link),
      ];
    }
    return NULL;
  }

  /**
   * Get link title.
   *
   * @param $link
   *   Link.
   *
   * @return mixed
   *   Return link title.
   */
  protected function getLinkTitle($link) {
    if (empty($link['title'])) {
      return $this->routes[$this->getLinkRoute($link)];
    }
    return $link['title'];
  }

  /**
   * Get link route.
   *
   * @param $link
   *   Link.
   *
   * @return mixed
   *   Return link route.
   */
  protected function getLinkRoute($link) {
    return $link['route'];
  }

  /**
   * Check if link is active.
   *
   * @param $link
   *   Link.
   *
   * @return boolean
   *   Return true if link is active or false.
   */
  protected function isLinkActive($link) {
    $current_route_name = $this->currentRouteMatch->getRouteName();
    if ($this->getLinkRoute($link) == $current_route_name) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if link is disabled.
   *
   * @param $link
   *   Link.
   *
   * @return bool
   *   True if route is disabled or flase.
   */
  protected function isLinkDisabled($link) {
    // Autogenerate titles
    if (empty($link['disabled'])) {
      return FALSE;
    }
    $disabled = $link['disabled'] == TRUE ? TRUE : FALSE;
    return $disabled;
  }

  /**
   * Routes
   */
  /**
   * Get available routes from system.
   *
   * @return array
   *   Array of available routes.
   */
  protected function getAvailableRoutes() {
    $allRoutes = $this->routeProvider->getAllRoutes();

    $routes = [];
    foreach ($allRoutes as $route_name => $route) {
      $title = $route->getDefault('_title');
      $routes[$route_name] = $title;
    }

    return $routes;
  }

  /**
   * Check if route is valid.
   *
   * @param $routeName
   *   Route name.
   *
   * @return bool
   *   True if route is valid or flase.
   */
  protected function isRouteValid($routeName) {
    $isValidRoute = array_key_exists($routeName, $this->routes);
    return $isValidRoute;
  }

  /**
   * Check if user has access to route.
   *
   * @param $routeName
   *   Route name.
   *
   * @return bool
   *   True if user has access to route or flase.
   */
  protected function isRouteAccessible($routeName) {
    return $this->accessManager->checkNamedRoute($routeName, [], $this->currentUser);
  }

}
