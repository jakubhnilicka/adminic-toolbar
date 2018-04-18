<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarPrimarySectionSection.php.
 */

/**
 * Class ToolbarPrimarySection.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarPrimarySection {

  /**
   * Section ID.
   *
   * @var string
   */
  private $id;

  /**
   * Section title.
   *
   * @var string
   */
  private $title;

  /**
   * Section links.
   *
   * @var array
   */
  private $links;

  /**
   * Tab where section belongs to.
   *
   * @var string
   */
  private $tab;

  /**
   * Type of section.
   *
   * @var string
   */
  private $type;

  /**
   * Section disabled state.
   *
   * @var bool
   */
  private $disabled;

  /**
   * Section constructor.
   *
   * @param string $id
   *   Section ID.
   * @param string $title
   *   Section title.
   * @param string $tab
   *   Tab where section belongs to.
   * @param bool $disabled
   *   Section disabled state.
   * @param string $type
   *   Type of section.
   */
  public function __construct(string $id, string $title, string $tab, bool $disabled, string $type) {
    $this->id = $id;
    $this->title = $title;
    $this->tab = $tab;
    $this->disabled = $disabled;
    $this->type = $type;
  }

  /**
   * Get section id.
   *
   * @return string
   *   Return section id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Get section title.
   *
   * @return string
   *   Return section title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Get section tab.
   *
   * @return string
   *   Return section tab.
   */
  public function getTab() {
    return $this->tab;
  }

  /**
   * Get section type.
   *
   * @return string
   *   Return section type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Has section type defined.
   *
   * @return bool
   *   Return TRUE if type is defined or FALSE.
   */
  public function hasType() {
    return !empty($this->type);
  }

  /**
   * Get section links.
   *
   * @return array
   *   Return array of links.
   */
  public function getLinks() {
    return $this->links;
  }

  /**
   * Set section links.
   *
   * @param array $links
   *   Return array of links.
   */
  public function setLinks(array $links) {
    $this->links = $links;
  }

  /**
   * Is tab disabled.
   *
   * @return bool
   *   If disabled return TRUE else FALSE.
   */
  public function isDisabled() {
    return $this->disabled;
  }

  /**
   * Return section render array.
   *
   * @return array
   *   Return section render array.
   */
  public function getRenderArray() {
    return [
      '#theme' => 'toolbar_primary_section',
      '#title' => $this->getTitle(),
      '#links' => $this->getLinks(),
    ];
  }

}
