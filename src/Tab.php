<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Url;

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
   * @var \Drupal\Core\Url
   */
  private $url;

  /**
   * @var string
   */
  private $title;

  /**
   * @var bool
   */
  private $active;

  /**
   * @var bool
   */
  private $disabled;

  private $badge;

  /**
   * Tab constructor.
   *
   * @param string $id
   * @param string $section
   * @param \Drupal\Core\Url $url
   * @param string $title
   * @param bool $active
   * @param bool $disabled
   * @param $badge
   */
  public function __construct(string $id, string $section, Url $url, string $title, bool $active, bool $disabled, $badge) {
    $this->id = $id;
    $this->section = $section;
    $this->url = $url;
    $this->title = $title;
    $this->active = $active;
    $this->disabled = $disabled;
    $this->badge = $badge;
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
   * Get tab state.
   *
   * @return string
   *   Return tab active state.
   */
  public function isDisabled() {
    return $this->active;
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
      '#theme' => 'toolbar_section_tab',
      '#title' => $this->getTitle(),
      '#route' => $this->getUrl(),
      '#active' => $this->isActive(),
      '#id' => $this->getId(),
      '#badge' => $this->getBadge(),
    ];
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
   * Get tab route.
   *
   * @return string
   *   Return tab route.
   */
  public function getUrl() {
    /** @var \Drupal\Core\Url $url */
    $url = $this->url;
    return $url->toString();
  }

  public function getRawUrl() {
    return $this->url;
  }
  /**
   * Get badge route.
   *
   * @return string
   *   Return tab route.
   */
  public function getBadge() {
    return $this->badge;
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
   * Get tab id.
   *
   * @return string
   *   Return tab id.
   */
  public function getId() {
    return $this->id;
  }

}
