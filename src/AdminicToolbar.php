<?php

namespace Drupal\adminic_toolbar;

use Drupal\adminic_toolbar\Components\Link;
use Drupal\adminic_toolbar\Components\Section;
use Drupal\adminic_toolbar\Components\Tab;
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
   * List of links.
   *
   * @var array|NULL
   */
  private $links;

  /**
   * List of active links.
   *
   * @var array|NULL
   */
  private $activeLinks;

  /**
   * List of sections.
   *
   * @var array|NULL
   */
  private $sections;

  /**
   * List of active sections.
   *
   * @var array|NULL
   */
  private $activeSections;

  /**
   * List of tabs.
   *
   * @var array|NULL
   */
  private $tabs;

  /**
   * List of active tabs.
   *
   * @var array|NULL
   */
  private $activeTabs;

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
    $this->create();
  }

  /**
   * Parse all necessary data.
   */
  protected function create() {
    $this->config = $this->loadConfig();
    $this->routes = $this->getAvailableRoutes();
    $this->links = $this->getLinks();
    $this->sections = $this->getSections();
    $this->tabs = $this->getTabs();
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
   * Get all defined links from all config files.
   *
   * @return array
   *   Array of links.
   */
  protected function getLinks(): array {
    $config = $this->config;
    $links = [];
    $activeLinks = [];
    $currentRouteName = $this->currentRouteMatch->getRouteName();

    foreach ($config as $configFile) {
      if (isset($configFile['links'])) {
        foreach ($configFile['links'] as $link) {
          $section = $link['section'];
          $route = $link['route'];
          $isValid = $this->isRouteValid($route);
          if ($isValid) {
            $title = isset($link['title']) ? $link['title'] : $this->routes[$route];
            $disabled = isset($link['disabled']) ? $link['disabled'] : FALSE;
            $active = FALSE;
            if ($disabled == FALSE && $isValid) {
              $key = sprintf('%s.%s', $section, $route);
              $newLink = new Link($section, $route, $title, $active);
              $links[$key] = $newLink;
              if ($route == $currentRouteName) {
                $newLink->setActive();
                $activeLinks[$key] = $newLink;
              }
            }
          }
        }
      }
    }

    $this->activeLinks = empty($activeLinks) ? NULL : $activeLinks;
    return $links;
  }

  /**
   * Get all defined sections from all config files.
   *
   * @return array
   *   Array of sections.
   */
  protected function getSections(): array {
    $config = $this->config;
    $sections = [];
    $activeSections = [];
    /** @var \Drupal\adminic_toolbar\Components\Link $activeLink */
    $activeLink = $this->getActiveLink();

    foreach ($config as $configFile) {
      if (isset($configFile['sections'])) {
        foreach ($configFile['sections'] as $section) {
          $id = $section['id'];
          $title = isset($section['title']) ? $section['title'] : NULL;
          $tab = isset($section['tab']) ? $section['tab'] : NULL;
          $disabled = isset($section['disabled']) ? $section['disabled'] : FALSE;
          if ($disabled == FALSE) {
            $newSection = new Section($id, $title, $tab);
            $sections[$id] = $newSection;
            if ($activeLink && $id == $activeLink->getSection()) {
              $activeSections[] = $newSection;
            }
          }
        }
      }
    }
    $this->activeSections = empty($activeSections) ? NULL : $activeSections;
    return $sections;
  }

  /**
   * Get all defined tabs from all config files.
   *
   * @return array
   *   Array of tabs.
   */
  protected function getTabs() {
    $config = $this->config;
    $tabs = [];
    $activeTabs = [];
    /** @var \Drupal\adminic_toolbar\Components\Section $activeSections */
    $activeSections =$this->getActiveSection();
    $currentRouteName = $this->currentRouteMatch->getRouteName();

    foreach ($config as $configFile) {
      if (isset($configFile['tabs'])) {
        foreach ($configFile['tabs'] as $tab) {
          $id = $tab['id'];
          $section = isset($tab['section']) ? $tab['section'] : NULL;
          $route = $tab['route'];
          $isValid = $this->isRouteValid($route);
          if ($isValid) {
            $title = isset($tab['title']) ? $tab['title'] : $this->routes[$route];
            $disabled = isset($tab['disabled']) ? $tab['disabled'] : FALSE;
            $active = FALSE;
            if ($disabled == FALSE && $isValid) {
              $newTab = new Tab($id, $section, $route, $title, $active);
              if ($activeSections && $id == $activeSections->getTab()) {
                $newTab->setActive();
                $activeTabs[$id] = $newTab;
              }
              elseif ($route == $currentRouteName) {
                $newTab->setActive();
                $activeTabs[$id] = $newTab;
              }
              $tabs[$id] = $newTab;
            }
          }
        }
      }
    }
    $this->activeTabs = empty($activeTabs) ? NULL : $activeTabs;
    return $tabs;
  }

  /**
   * Get render array for primary toolbar.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  public function getPrimaryToolbar() {
    $primarySections = $this->getPrimarySections();

    $sections = [];
    foreach ($primarySections as $section) {
      $sections[] = $this->getPrimarySection($section);
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
    $secondaryWrappers = $this->getSecondaryWrappers();
    $activeTab = $this->getActiveTab();
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

  /**
   * Get sections defined for primary toolbar.
   *
   * @return array
   *   Array of sections.
   */
  protected function getPrimarySections(): array {
    $sections = $this->sections;

    $primarySections = array_filter(
      $sections, function ($section) {
        return $section->getTab() == NULL;
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
    $sections = $this->sections;

    $secondarySections = [];
    if ($this->getActiveTab()) {
      /** @var \Drupal\adminic_toolbar\Components\Tab $activeTab */
      $activeTab = $this->getActiveTab();
      $secondarySections = array_filter(
        $sections, function ($section) use ($activeTab) {
          $tab = $section->getTab();
          return !empty($tab) && $tab == $activeTab->getId();
        }
      );
    }

    return $secondarySections;
  }

  protected function getSecondaryWrappers() {
    $tabs = $this->tabs;
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
    $sections = $this->sections;

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
   * @param \Drupal\adminic_toolbar\Components\Section $section
   *   Section.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  protected function getPrimarySection(Section $section) {
    $tabs = $this->tabs;
    $sectionId = $section->getId();

    $sectionValidTabs = array_filter(
      $tabs, function ($tab) use ($sectionId) {
        return $tab->getSection() == $sectionId;
      }
    );

    $sectionTabs = [];
    /** @var \Drupal\adminic_toolbar\Components\Tab $tab */
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
   * @param \Drupal\adminic_toolbar\Components\Section $section
   *   Section.
   *
   * @return array|null
   *   Retrun renderable array or null.
   */
  protected function getSecondarySection(Section $section) {
    $links = $this->links;
    $sectionId = $section->getId();

    $sectionValidLinks = array_filter(
      $links, function ($link) use ($sectionId) {
        return $link->getSection() == $sectionId;
      }
    );

    if(empty($sectionValidLinks)) {
      return NULL;
    }

    /** @var \Drupal\adminic_toolbar\Components\Link $link */
    $sectionLinks = [];
    foreach ($sectionValidLinks as $link) {
      $sectionLinks[] = $link->getRenderArray();
    }
    $section->setLinks($sectionLinks);
    return $section->getRenderArray();
  }

  /**
   * Get active link defined by current route.
   *
   * @return array
   *   Return first active link.
   */
  protected function getActiveLink() {
    $activeLinks = $this->activeLinks;
    if ($activeLinks) {
      return reset($activeLinks);
    }
    return NULL;
  }

  /**
   * Get active section defined by active link.
   *
   * @return array
   *   Return first active section.
   */
  protected function getActiveSection() {
    $activeSections = $this->activeSections;
    if ($activeSections) {
      return reset($activeSections);
    }
    return NULL;
  }

  /**
   * Get active tab defined by active session.
   *
   * @return array
   *   Return first active tab.
   */
  protected function getActiveTab() {
    $activeTabs = $this->activeTabs;
    if ($activeTabs) {
      return reset($activeTabs);
    }
    return NULL;
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
