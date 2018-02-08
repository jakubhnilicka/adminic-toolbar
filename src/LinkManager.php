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
    $this->parseLinks();
  }

  /**
   * Get all defined links from all config files.
   */
  public function parseLinks() {
    $config = $this->discoveryManager->getConfig();
    $currentRouteName = $this->routeManager->getCurrentRoute();

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
              $newLink = new Link($section, $route, $title, $active);
              $this->addLink($newLink);
              if ($route == $currentRouteName) {
                $newLink->setActive();
                $this->addActiveLink($newLink);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Add link.
   *
   * @param $tab
   */
  public function addLink($tab) {
    $this->links[] = $tab;
  }

  /**
   * Add active link.
   *
   * @param $tab
   */
  public function addActiveLink($tab) {
    $this->activeLinks[] = $tab;
  }

  /**
   * Get links.
   *
   * @return array
   */
  public function getLinks() {
    return $this->links;
  }
  /**
   * Get first active link.
   *
   * @return array
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