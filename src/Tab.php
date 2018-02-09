<?php

namespace Drupal\adminic_toolbar;

class Tab {

  /**
   * @var string
   */
  private $id;

  /**
   * @var string
   */
  private $section;

  /**
   * @var string
   */
  private $route;

  /**
   * @var string
   */
  private $title;

  /**
   * @var bool
   */
  private $active;

  /**
   * Tab constructor.
   *
   * @param string $id
   * @param string $section
   * @param string $route
   * @param string $title
   * @param bool $active
   */
  public function __construct(string $id, string $section, string $route, string $title, bool $active) {
    $this->id = $id;
    $this->section = $section;
    $this->route = $route;
    $this->title = $title;
    $this->active = $active;
  }

  /**
   * Get tab id.
   *
   * @return string
   *   Return tab id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Get tab section.
   *
   * @return string
   *   Return tab section.
   */
  public function getSection() {
    return $this->section;
  }

  /**
   * Get tab route.
   *
   * @return string
   *   Return tab route.
   */
  public function getRoute() {
    return $this->route;
  }

  /**
   * Get tab title.
   *
   * @return string
   *   Return tab title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Get tab state.
   *
   * @return string
   *   Return tab active state.
   */
  public function isActive() {
    return $this->active;
  }

  /**
   * Set tab as active.
   */
  public function setActive() {
    $this->active = TRUE;
  }

  /**
   * Set tab as inactive.
   */
  public function setInactive() {
    $this->active = FALSE;
  }

  /**
   * Return tab render array.
   *
   * @return array
   *   Return tab render array.
   */
  public function getRenderArray() {
    return [
      '#theme' => 'adminic_toolbar_section_tab',
      '#title' => $this->getTitle(),
      '#route' => $this->getRoute(),
      '#active' => $this->isActive(),
      '#id' => $this->getId(),
    ];
  }

}