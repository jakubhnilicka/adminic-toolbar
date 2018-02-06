<?php

namespace Drupal\adminic_toolbar\Components;

class Link {

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

  private $active;

  /**
   * Tab constructor.
   *
   * @param string $section
   * @param string $route
   * @param string $title
   * @param $active
   */
  public function __construct($section, $route, $title, $active) {
    $this->section = $section;
    $this->route = $route;
    $this->title = $title;
    $this->active = $active;
  }

  /**
   * Get link id.
   *
   * @return string
   *   Return link id.
   */
  public function getSection() {
    return $this->section;
  }

  /**
   * Get link route.
   *
   * @return string
   *   Return link route.
   */
  public function getRoute() {
    return $this->route;
  }

  /**
   * Get link title.
   *
   * @return string
   *   Return link title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Is link active.
   *
   * @return string
   *   Return link active state.
   */
  public function isActive() {
    return $this->active;
  }

  /**
   * Set link as active.
   */
  public function setActive() {
    $this->active = TRUE;
  }

  /**
   * Return link render array.
   *
   * @return array
   *   Return links render array.
   */
  public function getRenderArray() {
    return [
      '#theme' => 'adminic_toolbar_section_link',
      '#title' => $this->getTitle(),
      '#route' => $this->getRoute(),
      '#active' => $this->isActive(),
    ];
  }
}