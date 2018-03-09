<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Extension\ModuleHandler;

class LinkManager {

  /**
   * @var \Drupal\adminic_toolbar\DiscoveryManager
   */
  private $discoveryManager;

  /**
   * @var \Drupal\adminic_toolbar\RouteManager
   */
  private $routeManager;

  /**
   * @var array
   */
  private $links = [];

  /**
   * @var array
   */
  private $activeLinks = [];

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * LinkManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   * @param \Drupal\adminic_toolbar\RouteManager $routeManager
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
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
    return sprintf('%s.%s', $link->getWidget(), $link->getRoute());
  }

  /**
   * Get links.
   *
   * @return array
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
          $key = sprintf('%s.%s', $link['widget'], $link['route']);
          $configLinks[$key] = $link;
          $weight++;
        }
      }
    }
    uasort($configLinks, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    $this->moduleHandler->alter('toolbar_config_links', $configLinks);

    foreach ($configLinks as $link) {
      $widget = $link['widget'];
      $route = $link['route'];
      $isValid = $this->routeManager->isRouteValid($route);
      if ($isValid) {
        $title = isset($link['title']) ? $link['title'] : $this->routeManager->getDefaultTitle($route);
        $title = empty($title) ? '' : $title;
        $disabled = isset($link['disabled']) ? $link['disabled'] : FALSE;
        $badge = isset($link['badge']) ? $link['badge'] : NULL;
        $active = FALSE;
        $this->addLink(new Link($widget, $route, $title, $active, $disabled, $badge));
      }
    }
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
    // Remove link if exists and is disabled
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

}