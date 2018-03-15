<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * LinksManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;
use Exception;

/**
 * Class LinksManager.
 *
 * @package Drupal\adminic_toolbar
 */
class LinksManager {

  /**
   * Discovery manager.
   *
   * @var \Drupal\adminic_toolbar\DiscoveryManager
   */
  private $discoveryManager;

  /**
   * Route manager.
   *
   * @var \Drupal\adminic_toolbar\RouteManager
   */
  private $routeManager;

  /**
   * Array of links.
   *
   * @var array
   */
  private $links = [];

  /**
   * Array of active links.
   *
   * @var array
   */
  private $activeLinks = [];

  /**
   * Class that manages modules in a Drupal installation.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * LinksManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   *   Discovery manager.
   * @param \Drupal\adminic_toolbar\RouteManager $routeManager
   *   Route manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   */
  public function __construct(
    DiscoveryManager $discoveryManager,
    RouteManager $routeManager,
    ModuleHandler $moduleHandler) {
    $this->routeManager = $routeManager;
    $this->discoveryManager = $discoveryManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Add link to active links.
   *
   * @param \Drupal\adminic_toolbar\Link $link
   *   Link.
   */
  public function addActiveLink(Link $link) {
    $key = $this->getLinkKey($link);
    $this->activeLinks[$key] = $link;
  }

  /**
   * Get link unique key from section and route.
   *
   * @param \Drupal\adminic_toolbar\Link $link
   *   Link.
   *
   * @return string
   *   Return formated key.
   */
  public function getLinkKey(Link $link) {
    /** @var \Drupal\Core\Url $url */
    $url = $link->getRawUrl();
    $routeName = $url->getRouteName();
    $routeParams = $url->getRouteParameters();
    $routeParams = implode('.', $routeParams);
    $routeParams = empty($routeParams) ? '' : '.' . $routeParams;
    $key = $routeName . $routeParams;
    return sprintf('%s.%s', $link->getWidget(), $key);
  }

  /**
   * Get links.
   *
   * @return array
   *   Return array of links.
   *
   * @throws \Exception
   */
  public function getLinks() {
    if (empty($this->links)) {
      $this->parseLinks();
    }
    return $this->links;
  }

  /**
   * Get all defined links from all config files.
   */
  public function parseLinks() {
    $config = $this->discoveryManager->getConfig();

    $configLinks = [];
    $weight = 0;
    foreach ($config as $configFile) {
      if (isset($configFile['links'])) {
        foreach ($configFile['links'] as $link) {
          $link['weight'] = isset($link['weight']) ? $link['weight'] : $weight;
          $key = sprintf('%s.%s', $link['widget_id'], $link['route']);
          $configLinks[$key] = $link;
          $weight++;
        }
      }
    }

    // Sort links by weight.
    uasort($configLinks, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_config_links', $configLinks);

    $this->addLinks($configLinks);
  }

  /**
   * Add link.
   *
   * @param \Drupal\adminic_toolbar\Link $link
   *   Link.
   */
  public function addLink(Link $link) {
    $key = $this->getLinkKey($link);
    $this->links[$key] = $link;
    // Remove link if exists and is disabled.
    if (isset($this->links[$key]) && $link->isDisabled()) {
      unset($this->links[$key]);
    }
  }

  /**
   * Get first active link.
   *
   * @return \Drupal\adminic_toolbar\Link
   *   Return first active link.
   */
  public function getActiveLink() {
    $activeLinks = $this->activeLinks;
    if ($activeLinks) {
      return reset($activeLinks);
    }
    return NULL;
  }

  /**
   * Add links.
   *
   * @param array $configLinks
   *   Links array.
   */
  protected function addLinks(array $configLinks) {
    foreach ($configLinks as $link) {
      $this->validateLink($link);

      $widget_id = $link['widget_id'];
      $route = $link['route'];
      $route_params = isset($link['route_params']) ? $link['route_params'] : [];
      $isValid = $this->routeManager->isRouteValid($route, $route_params);

      if ($isValid) {
        $title = isset($link['title']) ? $link['title'] : $this->routeManager->getDefaultTitle($route, $route_params);
        $title = empty($title) ? '' : $title;
        $url = Url::fromRoute($route, $route_params);
        $disabled = isset($link['disabled']) ? $link['disabled'] : FALSE;
        $badge = isset($link['badge']) ? $link['badge'] : '';

        $active = FALSE;
        $this->addLink(new Link($widget_id, $url, $title, $active, $disabled, $badge));
      }
    }
  }

  /**
   * Validate link required parameters.
   *
   * @param array $link
   *   Links array.
   */
  protected function validateLink(array $link) {
    try {
      $obj = json_encode($link);
      if (!isset($link['widget_id'])) {
        throw new Exception('Link widget_id parameter missing ' . $obj);
      };
      if (!isset($link['route'])) {
        throw new Exception('Link route parameter missing ' . $obj);
      }
    }
    catch (Exception $e) {
      print $e->getMessage();
    }
  }

}
