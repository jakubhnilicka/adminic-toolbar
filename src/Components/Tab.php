<?php

namespace Drupal\adminic_toolbar\Components;

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
  public function __construct($id, $section, $route, $title, $active) {
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
   * Get tab id.
   *
   * @return string
   *   Return tab id.
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
   * Is tab active.
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
    ];
  }

}