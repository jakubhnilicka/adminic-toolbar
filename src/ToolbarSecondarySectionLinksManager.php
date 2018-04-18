<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarSecondarySectionLinksManagertionLinksManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;
use Exception;
use RuntimeException;

/**
 * Class ToolbarSecondarySectionLinksManager.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarSecondarySectionLinksManager {

  const LINKS = 'secondary_sections_links';
  const LINK_SECONDARY_SECTION = 'secondary_section_id';
  const LINK_ROUTE_NAME = 'route_name';
  const LINK_ROUTE_PARAMETERS = 'route_parameters';
  const LINK_TITLE = 'title';
  const LINK_BADGE = 'badge';
  const LINK_WEIGHT = 'weight';
  const LINK_DISABLED = 'disabled';

  /**
   * Discovery manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarConfigDiscovery
   */
  private $toolbarConfigDiscovery;

  /**
   * Route manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarRouteManager
   */
  private $toolbarRouteManager;

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
   * @param \Drupal\adminic_toolbar\ToolbarConfigDiscovery $toolbarConfigDiscovery
   *   Discovery manager.
   * @param \Drupal\adminic_toolbar\ToolbarRouteManager $toolbarRouteManager
   *   Route manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   */
  public function __construct(
    ToolbarConfigDiscovery $toolbarConfigDiscovery,
    ToolbarRouteManager $toolbarRouteManager,
    ModuleHandler $moduleHandler) {
    $this->toolbarRouteManager = $toolbarRouteManager;
    $this->toolbarConfigDiscovery = $toolbarConfigDiscovery;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Get all defined links from all config files.
   *
   * @throws \UnexpectedValueException
   * @throws \Drupal\Component\Discovery\DiscoveryException
   */
  public function discoverySecondarySectionsLinks() {
    $config = $this->toolbarConfigDiscovery->getConfig();

    $configSecondarySectionsLinks = [];
    $weight = 0;
    foreach ($config as $configFile) {
      if (isset($configFile[self::LINKS])) {
        /** @var array $configFileLinks */
        $configFileLinks = $configFile[self::LINKS];
        foreach ($configFileLinks as $link) {
          $link[self::LINK_WEIGHT] = $link[self::LINK_WEIGHT] ?? $weight++;
          $key = sprintf('%s.%s', $link[self::LINK_SECONDARY_SECTION], $link[self::LINK_ROUTE_NAME]);
          $configSecondarySectionsLinks[$key] = $link;
        }
      }
    }

    // Sort links by weight.
    uasort($configSecondarySectionsLinks, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_secondary_sections_links', $configSecondarySectionsLinks);

    $this->createLinksCollection($configSecondarySectionsLinks);
  }

  /**
   * Add links.
   *
   * @param array $configLinks
   *   Links array.
   *
   * @throws \UnexpectedValueException
   */
  protected function createLinksCollection(array $configLinks) {
    $currentRouteName = $this->toolbarRouteManager->getCurrentRoute();
    $activeRoutes = [];
    array_walk($configLinks, function ($link) use ($currentRouteName, &$activeRoutes) {
      if ($link['route_name'] === $currentRouteName) {
        $activeRoutes[$link['route_name']] = $link;
      }
    });
    $this->toolbarRouteManager->setActiveLinks($activeRoutes);
    if (empty($activeRoutes)) {
      $activeRoutes = $this->toolbarRouteManager->getActiveRoutesByPath();
    }

    foreach ($configLinks as $link) {
      $this->validateLink($link);

      $widget_id = $link[self::LINK_SECONDARY_SECTION];
      $route = $link[self::LINK_ROUTE_NAME];
      $route_params = $link[self::LINK_ROUTE_PARAMETERS] ?? [];
      $isValid = $this->toolbarRouteManager->isRouteValid($route, $route_params);

      if ($isValid) {
        $title = $link[self::LINK_TITLE] ?? $this->toolbarRouteManager->getDefaultTitle($route, $route_params);
        $title = empty($title) ? '' : $title;
        $url = Url::fromRoute($route, $route_params);
        $disabled = $link[self::LINK_DISABLED] ?? FALSE;
        $badge = $link[self::LINK_BADGE] ?? '';
        $active = FALSE;
        if (array_key_exists($route, $activeRoutes)) {
          $active = TRUE;
        }
        $this->addLink(new ToolbarSecondarySectionLink($widget_id, $url, $title, $active, $disabled, $badge));
      }
    }
  }

  /**
   * Add link.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSecondarySectionLink $link
   *   Link.
   *
   * @throws \UnexpectedValueException
   */
  public function addLink(ToolbarSecondarySectionLink $link) {
    $key = $this->getLinkKey($link);
    $this->links[$key] = $link;
    // Remove link if exists and is disabled.
    if (isset($this->links[$key]) && $link->isDisabled()) {
      unset($this->links[$key]);
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
      if (!isset($link[self::LINK_SECONDARY_SECTION])) {
        throw new RuntimeException('Link widget_id parameter missing ' . $obj);
      }
      if (!isset($link[self::LINK_ROUTE_NAME])) {
        throw new RuntimeException('Link route parameter missing ' . $obj);
      }
    }
    catch (Exception $e) {
      print $e->getMessage();
    }
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
    $links = &drupal_static(__FUNCTION__);
    if (!$links) {
      $this->discoverySecondarySectionsLinks();
      $links = $this->links;
    }
    return $links;
  }

  /**
   * Get link unique key from section and route.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSecondarySectionLink $link
   *   Link.
   *
   * @return string
   *   Return formated key.
   *
   * @throws \UnexpectedValueException
   */
  public function getLinkKey(ToolbarSecondarySectionLink $link) {
    /** @var \Drupal\Core\Url $url */
    $url = $link->getRawUrl();
    $routeName = $url->getRouteName();
    $routeParams = $url->getRouteParameters();
    $routeParams = implode('.', $routeParams);
    $routeParams = empty($routeParams) ? '' : '.' . $routeParams;
    $key = $routeName . $routeParams;
    return sprintf('%s.%s', $link->getToolbarPlugin(), $key);
  }

  /**
   * Add link to active links.
   *
   * @param \Drupal\adminic_toolbar\ToolbarSecondarySectionLink $link
   *   Link.
   *
   * @throws \UnexpectedValueException
   */
  public function addActiveLink(ToolbarSecondarySectionLink $link) {
    $key = $this->getLinkKey($link);
    $this->activeLinks[$key] = $link;
  }

  /**
   * Get first active tab.
   *
   * @return \Drupal\adminic_toolbar\ToolbarPrimarySectionTab
   *   Return first active tab.
   */
  public function getActiveLink() {
    $activeLinks = $this->toolbarRouteManager->getActiveLinks();
    if ($activeLinks) {
      return reset($activeLinks);
    }
    return NULL;
  }

  /**
   * Get active link url.
   *
   * @return null|string
   *   Retrun url or null.
   */
  public function getActiveLinkUrl() {
    $activeLink = $this->getActiveLink();
    if ($activeLink) {
      $routeName = $activeLink['route_name'];
      $routeParameters = $activeLink['route_parameters'] ?? [];
      $url = Url::fromRoute($routeName, $routeParameters);
      return $url->toString();
    }
    return NULL;
  }

}
