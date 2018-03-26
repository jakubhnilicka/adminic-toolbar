<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarPrimarySectionTab.phpSectionTab.php.
 */

use Drupal\Core\Url;

/**
 * Class ToolbarPrimarySectionTab.
 *
 * @package Drupal\adminic_toolbar
 */
class ToolbarPrimarySectionTab {

  /**
   * Tab ID.
   *
   * @var string
   */
  private $id;

  /**
   * Widget where tab belongs to.
   *
   * @var string
   */
  private $widget;

  /**
   * URL object for tab.
   *
   * @var \Drupal\Core\Url
   */
  private $url;

  /**
   * Tab title.
   *
   * @var string
   */
  private $title;

  /**
   * Tab active state.
   *
   * @var bool
   */
  private $active;

  /**
   * Tab disabled state.
   *
   * @var bool
   */
  private $disabled;

  /**
   * Tab badge.
   *
   * @var string
   */
  private $badge;

  /**
   * Tab constructor.
   *
   * @param string $id
   *   Tab ID.
   * @param string $widget
   *   Widget where tab belong to.
   * @param \Drupal\Core\Url $url
   *   URL object for tab.
   * @param string $title
   *   Tab title.
   * @param bool $active
   *   Tab active state.
   * @param bool $disabled
   *   Tab disabled state.
   * @param string $badge
   *   Tab badge.
   */
  public function __construct(string $id, string $widget, Url $url, string $title, bool $active, bool $disabled, string $badge) {
    $this->id = $id;
    $this->widget = $widget;
    $this->url = $url;
    $this->title = $title;
    $this->active = $active;
    $this->disabled = $disabled;
    $this->badge = $badge;
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
   * Get tab widget.
   *
   * @return string
   *   Return tab widget.
   */
  public function getWidget() {
    return $this->widget;
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
   * Get tab URL.
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
   * Get tab URL object.
   *
   * @return \Drupal\Core\Url
   *   Return tab URL object.
   */
  public function getRawUrl() {
    return $this->url;
  }

  /**
   * Get tab badge.
   *
   * @return string
   *   Return tab badge.
   */
  public function getBadge() {
    return $this->badge;
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
   * Set tab as inactive.
   */
  public function setInactive() {
    $this->active = FALSE;
  }

  /**
   * Is tab disabled.
   *
   * @return string
   *   If disabled return TRUE else FALSE.
   */
  public function isDisabled() {
    return $this->disabled;
  }

  /**
   * Return tab render array.
   *
   * @return array
   *   Return tab render array.
   */
  public function getRenderArray() {
    return [
      '#theme' => 'toolbar_primary_section_tab',
      '#id' => $this->getId(),
      '#title' => $this->getTitle(),
      '#url' => $this->getUrl(),
      '#badge' => $this->getBadge(),
    ];
  }

}
