<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarLinksManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;
use Exception;

/**
 * Class ToolbarLinksManager.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarLinksManager {

  const YML_LINKS_KEY = 'secondary_sections_links';
  const YML_LINKS_SECONDARY_SECTION_KEY = 'secondary_section_id';
  const YML_LINKS_ROUTE_NAME_KEY = 'route_name';
  const YML_LINKS_ROUTE_PARAMETERS_KEY = 'route_parameterss';
  const YML_LINKS_TITLE_KEY = 'title';
  const YML_LINKS_DISABLED_KEY = 'disabled';
  const YML_LINKS_BADGE_KEY = 'badge';
  const YML_LINKS_WEIGHT_KEY = 'weight';

  /**
   * Discovery manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarConfigDiscovery
   */
  private $discoveryManager;

  /**
   * Route manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarRouteManager
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
   * @param \Drupal\adminic_toolbar\ToolbarConfigDiscovery $discoveryManager
   *   Discovery manager.
   * @param \Drupal\adminic_toolbar\ToolbarRouteManager $routeManager
   *   Route manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   */
  public function __construct(
    ToolbarConfigDiscovery $discoveryManager,
    ToolbarRouteManager $routeManager,
    ModuleHandler $moduleHandler) {
    $this->routeManager = $routeManager;
    $this->discoveryManager = $discoveryManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Get all defined links from all config files.
   */
  public function parseLinks() {
    $config = $this->discoveryManager->getConfig();

    $configLinks = [];
    $weight = 0;
    foreach ($config as $configFile) {
      if (isset($configFile[self::YML_LINKS_KEY])) {
        foreach ($configFile[self::YML_LINKS_KEY] as $link) {
          $link[self::YML_LINKS_WEIGHT_KEY] = isset($link[self::YML_LINKS_WEIGHT_KEY]) ? $link[self::YML_LINKS_WEIGHT_KEY] : $weight++;
          $key = sprintf('%s.%s', $link[self::YML_LINKS_SECONDARY_SECTION_KEY], $link[self::YML_LINKS_ROUTE_NAME_KEY]);
          $configLinks[$key] = $link;
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
   * Add links.
   *
   * @param array $configLinks
   *   Links array.
   */
  protected function addLinks(array $configLinks) {
    foreach ($configLinks as $link) {
      $this->validateLink($link);

      $widget_id = $link[self::YML_LINKS_SECONDARY_SECTION_KEY];
      $route = $link[self::YML_LINKS_ROUTE_NAME_KEY];
      $route_params = isset($link[self::YML_LINKS_ROUTE_PARAMETERS_KEY]) ? $link[self::YML_LINKS_ROUTE_PARAMETERS_KEY] : [];
      $isValid = $this->routeManager->isRouteValid($route, $route_params);

      if ($isValid) {
        $title = isset($link[self::YML_LINKS_TITLE_KEY]) ? $link[self::YML_LINKS_TITLE_KEY] : $this->routeManager->getDefaultTitle($route, $route_params);
        $title = empty($title) ? '' : $title;
        $url = Url::fromRoute($route, $route_params);
        $disabled = isset($link[self::YML_LINKS_DISABLED_KEY]) ? $link[self::YML_LINKS_DISABLED_KEY] : FALSE;
        $badge = isset($link[self::YML_LINKS_BADGE_KEY]) ? $link[self::YML_LINKS_BADGE_KEY] : '';

        $active = FALSE;
        $this->addLink(new ToolbarLink($widget_id, $url, $title, $active, $disabled, $badge));
      }
    }
  }

  /**
   * Add link.
   *
   * @param \Drupal\adminic_toolbar\ToolbarLink $link
   *   Link.
   */
  public function addLink(ToolbarLink $link) {
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
      if (!isset($link[self::YML_LINKS_SECONDARY_SECTION_KEY])) {
        throw new Exception('Link widget_id parameter missing ' . $obj);
      };
      if (!isset($link[self::YML_LINKS_ROUTE_NAME_KEY])) {
        throw new Exception('Link route parameter missing ' . $obj);
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
    if (empty($this->links)) {
      $this->parseLinks();
    }
    return $this->links;
  }

  /**
   * Get link unique key from section and route.
   *
   * @param \Drupal\adminic_toolbar\ToolbarLink $link
   *   Link.
   *
   * @return string
   *   Return formated key.
   */
  public function getLinkKey(ToolbarLink $link) {
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
   * @param \Drupal\adminic_toolbar\ToolbarLink $link
   *   Link.
   */
  public function addActiveLink(ToolbarLink $link) {
    $key = $this->getLinkKey($link);
    $this->activeLinks[$key] = $link;
  }

  /**
   * Get first active link.
   *
   * @return \Drupal\adminic_toolbar\ToolbarLink
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
