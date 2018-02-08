<?php
/**
 * Created by PhpStorm.
 * User: jakubhnilicka
 * Date: 07.02.18
 * Time: 20:19
 */

namespace Drupal\adminic_toolbar;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountProxy;

class TabManager {

  /**
   * @var array
   */
  private $tabs = [];

  /**
   * @var array
   */
  private $activeTabs = [];

  /**
   * @var \Drupal\adminic_toolbar\DiscoveryManager
   */
  private $discoveryManager;

  /**
   * @var \Drupal\adminic_toolbar\RouteManager
   */
  private $routeManager;

  /**
   * @var \Drupal\adminic_toolbar\SectionManager
   */
  private $sectionManager;

  /**
   * TabManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   * @param \Drupal\adminic_toolbar\RouteManager $routeManager
   * @param \Drupal\adminic_toolbar\SectionManager $sectionManager
   */
  public function __construct(
    DiscoveryManager $discoveryManager,
    RouteManager $routeManager,
    SectionManager $sectionManager) {
    $this->discoveryManager = $discoveryManager;
    $this->routeManager = $routeManager;
    $this->sectionManager = $sectionManager;
    $this->parseTabs();
  }

  /**
   * Get all defined tabs from all config files.
   *
   * @return bool
   *   Array of tabs.
   */
  protected function parseTabs() {
    $config = $this->discoveryManager->getConfig();
    $activeSections =$this->sectionManager->getActiveSection();
    $currentRouteName = $this->routeManager->getCurrentRoute();

    foreach ($config as $configFile) {
      if ($configFile['set']['id'] == 'default' && isset($configFile['set']['tabs'])) {
        foreach ($configFile['set']['tabs'] as $tab) {
          $id = $tab['id'];
          $section = isset($tab['section']) ? $tab['section'] : NULL;
          $route = $tab['route'];
          $isValid = $this->routeManager->isRouteValid($route);
          if ($isValid) {
            $title = isset($tab['title']) ? $tab['title'] : $this->routeManager->getDefaultTitle($route);
            $disabled = isset($tab['disabled']) ? $tab['disabled'] : FALSE;
            $active = FALSE;
            if ($disabled == FALSE && $isValid) {
              $newTab = new Tab($id, $section, $route, $title, $active);
              if ($activeSections && $id == $activeSections->getTab()) {
                $newTab->setActive();
                $this->addActiveTab($newTab);
              }
              elseif ($route == $currentRouteName) {
                $newTab->setActive();
                $this->addActiveTab($newTab);
              }
              $this->addTab($newTab);
            }
          }
        }
      }
    }
    return TRUE;
  }

  public function addTab($tab) {
    $this->tabs[] = $tab;
  }

  public function addActiveTab($tab) {
    $this->activeTabs[] = $tab;
  }

  public function getTabs() {
    return $this->tabs;
  }
  /**
   * Get active tab defined by active session.
   *
   * @return array
   *   Return first active tab.
   */
  public function getActiveTab() {
    $activeTabs = $this->activeTabs;
    if ($activeTabs) {
      return reset($activeTabs);
    }
    return NULL;
  }
}