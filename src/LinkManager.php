<?php

namespace Drupal\adminic_toolbar;

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
   * LinkManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   * @param \Drupal\adminic_toolbar\RouteManager $routeManager
   */
  public function __construct(
    DiscoveryManager $discoveryManager,
    RouteManager $routeManager) {
    $this->routeManager = $routeManager;
    $this->discoveryManager = $discoveryManager;
  }

  /**
   * Get all defined links from all config files.
   */
  public function parseLinks() {
    $config = $this->discoveryManager->getConfig();

    $configLinks = [];
    foreach ($config as $configFile) {
      if ($configFile['set']['id'] == 'default' && isset($configFile['set']['links'])) {
        foreach ($configFile['set']['links'] as $link) {
          $link['weight'] = isset($link['weight']) ? $link['weight'] : 0;
          $configLinks[] = $link;
        }
      }
    }
    uasort($configLinks, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    foreach ($configLinks as $link) {
      $section = $link['section'];
      $route = $link['route'];
      $isValid = $this->routeManager->isRouteValid($route);
      if ($isValid) {
        $title = isset($link['title']) ? $link['title'] : $this->routeManager->getDefaultTitle($route);
        $disabled = isset($link['disabled']) ? $link['disabled'] : FALSE;
        $active = FALSE;
        $this->addLink(new Link($section, $route, $title, $active, $disabled));
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
    if (isset($this->links[$key]) && $link->isDisabled() ) {
      unset($this->links[$key]);
    }
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
    return sprintf('%s.%s', $link->getSection(), $link->getRoute());
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