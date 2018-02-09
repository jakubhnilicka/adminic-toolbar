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

    foreach ($config as $configFile) {
      if ($configFile['set']['id'] == 'default' && isset($configFile['set']['links'])) {
        foreach ($configFile['set']['links'] as $link) {
          $section = $link['section'];
          $route = $link['route'];
          $isValid = $this->routeManager->isRouteValid($route);
          if ($isValid) {
            $title = isset($link['title']) ? $link['title'] : $this->routeManager->getDefaultTitle($route);
            $disabled = isset($link['disabled']) ? $link['disabled'] : FALSE;
            $active = FALSE;
            if ($disabled == FALSE && $isValid) {
              $this->addLink(new Link($section, $route, $title, $active));
            }
          }
        }
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