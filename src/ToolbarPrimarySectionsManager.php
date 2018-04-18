<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarPrimarySectionsManagerManager.php.
 */

use Drupal\Core\Extension\ModuleHandler;
use Exception;
use RuntimeException;

/**
 * Class ToolbarPrimarySectionsManager.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarPrimarySectionsManager {

  const PRIMARY_SECTIONS = 'primary_sections';
  const SECTION_ID = 'id';
  const SECTION_TAB_ID = 'tab_id';
  const SECTION_PLUGIN_ID = 'plugin_id';
  const SECTION_TITLE = 'title';
  const SECTION_PRESET = 'preset';
  const SECTION_WEIGHT = 'weight';
  const SECTION_DISABLED = 'disabled';

  /**
   * Discovery manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarConfigDiscovery
   */
  private $toolbarConfigDiscovery;

  /**
   * Tabs manager.
   *
   * @var \Drupal\adminic_toolbar\ToolbarPrimarySectionTabsManager
   */
  private $toolbarTabManager;

  /**
   * Primary Sections.
   *
   * @var array
   */
  private $primarySections = [];

  /**
   * Class that manages modules in a Drupal installation.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * SectionsManager constructor.
   *
   * @param \Drupal\adminic_toolbar\ToolbarConfigDiscovery $toolbarConfigDiscovery
   *   Toolbar config discovery.
   * @param \Drupal\adminic_toolbar\ToolbarPrimarySectionTabsManager $toolbarTabsManager
   *   Toolbar tabs manager.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   Class that manages modules in a Drupal installation.
   */
  public function __construct(
    ToolbarConfigDiscovery $toolbarConfigDiscovery,
    ToolbarPrimarySectionTabsManager $toolbarTabsManager,
    ModuleHandler $moduleHandler) {
    $this->toolbarConfigDiscovery = $toolbarConfigDiscovery;
    $this->toolbarTabManager = $toolbarTabsManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Get all defined sections from all config files.
   *
   * @throws \Exception
   */
  protected function discoveryPrimarySections() {
    $config = $this->toolbarConfigDiscovery->getConfig();

    $weight = 0;
    $configPrimarySections = [];
    foreach ($config as $configFile) {
      if (isset($configFile[self::PRIMARY_SECTIONS])) {
        /** @var array $configFileSections */
        $configFileSections = $configFile[self::PRIMARY_SECTIONS];
        foreach ($configFileSections as $section) {
          // If weight is empty set computed value.
          $section[self::SECTION_WEIGHT] = $section[self::SECTION_WEIGHT] ?? $weight;
          // If set is empty set default set.
          $section[self::SECTION_PRESET] = $section[self::SECTION_PRESET] ?? 'default';
          // TODO: get key from method.
          $key = $section[self::SECTION_ID];
          $configPrimarySections[$key] = $section;
          $weight++;
        }
      }
    }
    // Sort tabs by weight.
    uasort($configPrimarySections, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    // Call hook alters.
    $this->moduleHandler->alter('toolbar_primary_sections', $configPrimarySections);

    // Add tabs.
    $this->createPrimarySectionsCollection($configPrimarySections);
  }

  /**
   * Add sections.
   *
   * @param array $configSections
   *   Array of sections.
   */
  protected function createPrimarySectionsCollection(array $configSections) {
    foreach ($configSections as $section) {
      /** @var array $sectionPreset */
      $sectionPreset = $section[self::SECTION_PRESET];
      if ($sectionPreset === $this->toolbarConfigDiscovery->getActiveSet()) {
        $this->validatePrimarySectionInput($section);

        $id = $section[self::SECTION_ID];
        $title = $section[self::SECTION_TITLE] ?? '';
        $tab_id = $section[self::SECTION_TAB_ID] ?? '';
        $disabled = $section[self::SECTION_DISABLED] ?? FALSE;
        $type = $section[self::SECTION_PLUGIN_ID] ?? '';
        $newSection = new ToolbarPrimarySection($id, $title, $tab_id, $disabled, $type);
        $this->addPrimarySection($newSection);
      }
    }
  }

  /**
   * Add section.
   *
   * @param \Drupal\adminic_toolbar\ToolbarPrimarySection $section
   *   Section.
   */
  public function addPrimarySection(ToolbarPrimarySection $section) {
    $key = $this->getSectionKey($section);
    $this->primarySections[$key] = $section;
    // Remove section if exists but is disabled.
    if (isset($this->primarySections[$key]) && $section->isDisabled()) {
      unset($this->primarySections[$key]);
    }
  }

  /**
   * Get sections defined for primary toolbar.
   *
   * @return array
   *   Array of sections.
   *
   * @throws \Exception
   */
  public function getPrimarySections() {
    if (empty($this->primarySections)) {
      $this->discoveryPrimarySections();
    }

    return $this->primarySections;
  }

  /**
   * Get renderable array for primary section.
   *
   * @param \Drupal\adminic_toolbar\ToolbarPrimarySection $section
   *   Section.
   *
   * @return array|null
   *   Return renderable array or NULL.
   *
   * @throws \Drupal\Component\Discovery\DiscoveryException
   */
  public function getPrimarySection(ToolbarPrimarySection $section) {
    $tabs = $this->toolbarTabManager->getTabs();
    $sectionId = $section->getId();

    $sectionValidTabs = array_filter(
      $tabs, function ($tab) use ($sectionId) {
        /** @var \Drupal\adminic_toolbar\ToolbarPrimarySectionTab $tab */
        return $tab->getWidget() === $sectionId;
      }
    );

    $sectionTabs = [];
    /** @var \Drupal\adminic_toolbar\ToolbarPrimarySectionTab $tab */
    foreach ($sectionValidTabs as $tab) {
      $sectionTabs[] = $tab->getRenderArray();
    }

    if ($sectionTabs) {
      $section->setLinks($sectionTabs);
      return $section->getRenderArray();
    }

    return NULL;
  }

  /**
   * Get section key.
   *
   * @param \Drupal\adminic_toolbar\ToolbarPrimarySection $section
   *   Section.
   *
   * @return string
   *   Return section key.
   */
  protected function getSectionKey(ToolbarPrimarySection $section) {
    return $section->getId();
  }

  /**
   * Validate section required parameters.
   *
   * @param array $section
   *   Section array.
   */
  protected function validatePrimarySectionInput(array $section) {
    try {
      $obj = json_encode($section);
      if (empty($section[self::SECTION_ID])) {
        throw new RuntimeException('Section ID parameter missing ' . $obj);
      }
    }
    catch (Exception $e) {
      print $e->getMessage();
    }
  }

}
