<?php

namespace Drupal\adminic_toolbar;

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

  /*
   * @var bool
   */
  private $active;
  /**
   * @var bool
   */
  private $disabled;

  /**
   * Link constructor.
   *
   * @param string $section
   * @param string $route
   * @param string $title
   * @param bool $active
   * @param bool $disabled
   */
  public function __construct(string $section, string $route, string $title, bool $active, bool $disabled) {
    $this->section = $section;
    $this->route = $route;
    $this->title = $title;
    $this->active = $active;
    $this->disabled = $disabled;
  }

  /**
   * Get link section.
   *
   * @return string
   *   Return link section.
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
   * Set link as inactive.
   */
  public function setInactive() {
    $this->active = FALSE;
  }

  public function isDisabled() {
    return $this->disabled;
  }
  /**
   * Return link render array.
   *
   * @return array
   *   Return links render array.
   */
  public function getRenderArray() {
    return [
      '#theme' => 'toolbar_section_link',
      '#title' => $this->getTitle(),
      '#route' => $this->getRoute(),
      '#active' => $this->isActive(),
    ];
  }

}
