<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountProxy;

class RouteManager {

  /**
   * @var \Drupal\Core\Routing\RouteProvider
   */
  private $routeProvider;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * @var \Drupal\Core\Access\AccessManager
   */
  private $accessManager;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  private $currentUser;

  /**
   * @var array
   */
  private $routes = [];

  /**
   * RouteManager constructor.
   *
   * @param \Drupal\Core\Routing\RouteProvider $routeProvider
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   * @param \Drupal\Core\Access\AccessManager $accessManager
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   */
  public function __construct(
    RouteProvider $routeProvider,
    CurrentRouteMatch $currentRouteMatch,
    AccessManager $accessManager,
    AccountProxy $currentUser) {
    $this->routeProvider = $routeProvider;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->accessManager = $accessManager;
    $this->currentUser = $currentUser;
  }

  /**
   * Get available routes from drupal.
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
   * Get current route name.
   *
   * @return null|string
   */
  public function getCurrentRoute() {
    return $this->currentRouteMatch->getRouteName();
  }

  /**
   * Check if route is valid.
   *
   * @param string $routeName
   *   Route name.
   *
   * @return bool
   *   True if route is valid or flase.
   */
  public function isRouteValid(string $routeName) {
    $isValidRoute = array_key_exists($routeName, $this->getRoutes());
    if (!$isValidRoute) {
      return FALSE;
    }
    $isRouteAccessible = $this->isRouteAccessible($routeName);
    if (!$isRouteAccessible) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check if current user has access to route.
   *
   * @param string $routeName
   *   Route name.
   *
   * @return bool
   *   True if user has access to route or flase.
   */
  public function isRouteAccessible(string $routeName) {
    return $this->accessManager->checkNamedRoute($routeName, [], $this->currentUser);
  }

  /**
   * Get routes.
   *
   * @return array
   */
  public function getRoutes() {
    if (empty($this->routes)) {
      $this->routes = $this->getAvailableRoutes();
    }

    return $this->routes;
  }

  /**
   * Get route default title.
   *
   * @param string $routeName
   *
   * @return mixed
   */
  public function getDefaultTitle(string $routeName) {
    if ($this->isRouteValid($routeName)) {
      $routes = $this->getRoutes();

      return $routes[$routeName];
    }

    return NULL;
  }

}