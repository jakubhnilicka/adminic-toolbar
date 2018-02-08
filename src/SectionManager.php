<?php
/**
 * Created by PhpStorm.
 * User: jakubhnilicka
 * Date: 07.02.18
 * Time: 20:19
 */

namespace Drupal\adminic_toolbar;

class SectionManager {

  private $sections = [];
  private $activeSections = [];
  /**
   * @var \Drupal\adminic_toolbar\DiscoveryManager
   */
  private $discoveryManager;
  /**
   * @var \Drupal\adminic_toolbar\LinkManager
   */
  private $linkManager;

  /**
   * SectionManager constructor.
   *
   * @param \Drupal\adminic_toolbar\DiscoveryManager $discoveryManager
   * @param \Drupal\adminic_toolbar\LinkManager $linkManager
   */
  public function __construct(
    DiscoveryManager $discoveryManager,
    LinkManager $linkManager) {
    $this->discoveryManager = $discoveryManager;
    $this->linkManager = $linkManager;
    $this->parseSections();
  }

  /**
   * Get all defined sections from all config files.
   *
   * @return boolean
   *   Array of sections.
   */
  protected function parseSections() {
    $config = $this->discoveryManager->getConfig();
    $activeLink = $this->linkManager->getActiveLink();

    foreach ($config as $configFile) {
      if ($configFile['set']['id'] == 'default' && isset($configFile['set']['sections'])) {
        foreach ($configFile['set']['sections'] as $section) {
          $id = $section['id'];
          $title = isset($section['title']) ? $section['title'] : NULL;
          $tab = isset($section['tab']) ? $section['tab'] : NULL;
          $disabled = isset($section['disabled']) ? $section['disabled'] : FALSE;
          $callback = isset($section['callback']) ? $section['callback'] : NULL;
          if ($disabled == FALSE) {
            $newSection = new Section($id, $title, $tab, $callback);
            $this->addSection($newSection);
            if ($activeLink && $id == $activeLink->getSection()) {
              $this->addActiveSection($newSection);
            }
          }
        }
      }
    }
    return TRUE;
  }

  public function addSection($section) {
    $this->sections[] = $section;
  }

  public function addActiveSection($section) {
    $this->activeSections[] = $section;
  }

  public function getSections() {
    return $this->sections;
  }
  /**
   * Get active tab defined by active session.
   *
   * @return array
   *   Return first active tab.
   */
  public function getActiveSection() {
    $activeSections = $this->activeSections;
    if ($activeSections) {
      return reset($activeSections);
    }
    return NULL;
  }

  /**
   * Get sections defined for primary toolbar.
   *
   * @return array
   *   Array of sections.
   */
  public function getPrimarySections(): array {
    $sections = $this->getSections();

    $primarySections = array_filter(
      $sections, function ($section) {
      return $section->getTab() == NULL;
    }
    );

    return $primarySections;
  }



}