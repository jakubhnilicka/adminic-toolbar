<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarRouteManager.php.
 */

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountProxy;

/**
 * Class ToolbarRouteManager.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarRouteManager {

  /**
   * A Route Provider front-end for all Drupal-stored routes.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  private $routeProvider;

  /**
   * Default object for current_route_match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * Attaches access check services to routes and runs them on request.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  private $accessManager;

  /**
   * A proxied implementation of AccountInterface.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  private $currentUser;

  /**
   * Array of routes.
   *
   * @var array
   */
  private $routes = [];

  /**
   * Array of active routes.
   *
   * @var array
   */
  private $activeRoutes = [];
  private $activeSecondarySection;

  /**
   * RouteManager constructor.
   *
   * @param \Drupal\Core\Routing\RouteProvider $routeProvider
   *   A Route Provider front-end for all Drupal-stored routes.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Default object for current_route_match service.
   * @param \Drupal\Core\Access\AccessManager $accessManager
   *   Attaches access check services to routes and runs them on request.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   A proxied implementation of AccountInterface.
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
   * Returns the route name.
   *
   * @return string|null
   *   The route name. NULL if no route is matched.
   */
  public function getCurrentRoute() {
    return $this->currentRouteMatch->getRouteName();
  }

  /**
   * Get route default title.
   *
   * @param string $routeName
   *   Route name.
   * @param array $routeParameters
   *   Route parameters.
   *
   * @return mixed
   *   Route title. NULL if route don't have title.
   */
  public function getDefaultTitle(string $routeName, array $routeParameters) {
    if ($this->isRouteValid($routeName, $routeParameters)) {
      $routes = $this->getRoutes();

      return $routes[$routeName]['title'];
    }

    return NULL;
  }

  /**
   * Check if route is valid and accesible for current user..
   *
   * @param string $routeName
   *   Route name.
   * @param array $routeParams
   *   Route Parameters.
   *
   * @return bool
   *   TRUE if route is valid and accessible or FALSE.
   */
  public function isRouteValid(string $routeName, array $routeParams) {
    $isValidRoute = array_key_exists($routeName, $this->getRoutes());
    if (!$isValidRoute) {
      return FALSE;
    }

    /** @var array $requiredParameters */
    $requiredParameters = $this->getRoutes()[$routeName]['parameters'];
    foreach ($requiredParameters as $parameter) {
      if (!array_key_exists($parameter, $routeParams)) {
        return FALSE;
      }
    }

    $isRouteAccessible = $this->isRouteAccessible($routeName);
    if (!$isRouteAccessible) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get list of routes.
   *
   * @return array
   *   Array of available routes.
   */
  public function getRoutes() {
    if (empty($this->routes)) {
      $this->routes = $this->getAvailableRoutes();
    }

    return $this->routes;
  }

  /**
   * Get all the routes on the system simplified to array.
   *
   * @return array
   *   Array of system drupal routes.
   */
  protected function getAvailableRoutes() {
    $allRoutes = $this->routeProvider->getAllRoutes();

    $routes = [];
    foreach ($allRoutes as $routeName => $route) {
      $parameters = $route->getOption('parameters');
      $requiredParameters = empty($parameters) ? [] : array_keys($parameters);
      $title = $route->getDefault('_title');
      $routes[$routeName] = [
        'title' => $title,
        'parameters' => $requiredParameters,
      ];
    }

    return $routes;
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
    // TODO: Add route parameters validation.
    return $this->accessManager->checkNamedRoute($routeName, [], $this->currentUser);
  }

  /**
   * Set active routes.
   *
   * @todo Explain, what is Active route.
   *
   * @return array
   *   Return array of active routes.
   */
  public function getActiveRoutesByPath() {
    // TODO: set active route if in config by config.
    $activeRoutes = [];
    $currentRouteObject = $this->currentRouteMatch->getRouteObject();
    $allRoutes = $this->routeProvider->getAllRoutes();

    if (!$currentRouteObject) {
      return $activeRoutes;
    }

    $currentPath = $currentRouteObject->getPath();

    foreach ($allRoutes as $route_name => $route) {
      $path = $route->getPath();
      if (strpos($currentPath, $path) === 0) {
        $activeRoutes[$route_name] = $route;
      }
    }

    return $activeRoutes;
  }

  /**
   * Set active links.
   *
   * @param array $activeRoutes
   *   Active routes.
   */
  public function setActiveLinks(array $activeRoutes) {
    $this->activeRoutes = $activeRoutes;
  }

  /**
   * Get array of active routes.
   *
   * @todo Explain, what is Active route.
   *
   * @return array
   *   Return array of active routes.
   */
  public function getActiveLinks() {
    return $this->activeRoutes;
  }

  /**
   * Set active secondary section.
   *
   * @param array $secondarySection
   *   Secondary section.
   */
  public function setActiveSecondarySection(array $secondarySection) {
    $this->activeSecondarySection = $secondarySection;
  }

  /**
   * Get secondary active section.
   *
   * @return mixed
   *   Secondary section.
   */
  public function getActiveSecondarySection() {
    return $this->activeSecondarySection;
  }

}
