<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Url;

class Link {

  /**
   * @var string
   */
  private $widget;

  /**
   * @var \Drupal\Core\Url
   */
  private $url;

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

  private $badge;

  /**
   * Link constructor.
   *
   * @param string $widget
   * @param \Drupal\Core\Url $url
   * @param string $title
   * @param bool $active
   * @param bool $disabled
   * @param $badge
   */
  public function __construct(string $widget, Url $url, string $title, bool $active, bool $disabled, $badge) {
    $this->widget = $widget;
    $this->url = $url;
    $this->title = $title;
    $this->active = $active;
    $this->disabled = $disabled;
    $this->badge = $badge;
  }

  /**
   * Get link section.
   *
   * @return string
   *   Return link section.
   */
  public function getWidget() {
    return $this->widget;
  }

  public function getBadge() {
    return $this->badge;
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
      '#route' => $this->getUrl(),
      '#active' => $this->isActive(),
      '#badge' => $this->getBadge(),
    ];
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
   * Get link route.
   *
   * @return string
   *   Return link route.
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

}
