<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarSecondarySectionLink.phpySectionLink.php.
 */

use Drupal\Core\Url;

/**
 * Class ToolbarSecondarySectionLink.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarSecondarySectionLink {

  /**
   * Widget where link belongs to.
   *
   * @var string
   */
  private $widget;

  /**
   * URL object for link.
   *
   * @var \Drupal\Core\Url
   */
  private $url;

  /**
   * Link title.
   *
   * @var string
   */
  private $title;

  /**
   * Link active state.
   *
   * @var bool
   */
  private $active;

  /**
   * Link disabled state.
   *
   * @var bool
   */
  private $disabled;

  /**
   * Link badge.
   *
   * @var string
   */
  private $badge;

  /**
   * Link constructor.
   *
   * @param string $widget
   *   Widget where link belong to.
   * @param \Drupal\Core\Url $url
   *   URL object for link.
   * @param string $title
   *   Link title.
   * @param bool $active
   *   Link active state.
   * @param bool $disabled
   *   Link disabled state.
   * @param string $badge
   *   Link badge.
   */
  public function __construct(string $widget, Url $url, string $title, bool $active, bool $disabled, string $badge) {
    $this->widget = $widget;
    $this->url = $url;
    $this->title = $title;
    $this->active = $active;
    $this->disabled = $disabled;
    $this->badge = $badge;
  }

  /**
   * Get link widget.
   *
   * @return string
   *   Return link widget.
   */
  public function getToolbarPlugin() {
    return $this->widget;
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
   * Get link URL.
   *
   * @return string
   *   Return link URL as string.
   */
  public function getUrl() {
    /** @var \Drupal\Core\Url $url */
    $url = $this->url;
    return $url->toString();
  }

  /**
   * Get link URL object.
   *
   * @return \Drupal\Core\Url
   *   Return link URL object.
   */
  public function getRawUrl() {
    return $this->url;
  }

  /**
   * Get link badge.
   *
   * @return mixed
   *   Return link badge.
   */
  public function getBadge() {
    return $this->badge;
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

  /**
   * Is link disabled.
   *
   * @return bool
   *   If disabled return TRUE else FALSE.
   */
  public function isDisabled() {
    return $this->disabled;
  }

  /**
   * Return link render array.
   *
   * @return array
   *   Return link render array.
   */
  public function getRenderArray() {
    return [
      '#theme' => 'toolbar_secondary_section_link',
      '#title' => $this->getTitle(),
      '#url' => $this->getUrl(),
      '#badge' => $this->getBadge(),
    ];
  }

}
