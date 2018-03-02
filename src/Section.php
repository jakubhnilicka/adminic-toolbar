<?php

namespace Drupal\adminic_toolbar;

class Section {

  private $title;

  private $links = NULL;

  private $id;

  private $tab;

  private $type;

  private $disabled;

  /**
   * Section constructor.
   *
   * @param string $id
   * @param string|null $title
   * @param string $tab
   * @param $disabled
   * @param string $type
   */
  public function __construct($id, $title, $tab, $disabled, $type) {
    $this->id = $id;
    $this->title = $title;
    $this->tab = $tab;
    $this->type = $type;
    $this->disabled = $disabled;
  }

  /**
   * Get section id.
   *
   * @return string
   *   Retrun section id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Get section title.
   *
   * @return string
   *   Retrun section title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Get section tab.
   *
   * @return string
   *   Retrun section tab.
   */
  public function getTab() {
    return $this->tab;
  }

  /**
   * Get section callback.
   *
   * @return string
   *   Retrun section callback.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set section links.
   *
   * @param array $links
   *   Array of links.
   */
  public function setLinks($links) {
    $this->links = $links;
  }

  /**
   * Get section links.
   *
   * @return array
   *   Retrun array of links.
   */
  public function getLinks() {
    return $this->links;
  }

  /**
   * Has section callback?
   *
   * @return bool
   */
  public function hasType() {
    return !is_null($this->type);
  }

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
      '#theme' => 'toolbar_section',
      '#title' => $this->getTitle(),
      '#links' => $this->getLinks(),
    ];
  }

}
