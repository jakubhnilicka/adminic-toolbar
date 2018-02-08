<?php

namespace Drupal\adminic_toolbar;

class Section {

  private $title;

  private $links = NULL;

  private $id;

  private $tab;

  private $callback;

  /**
   * Section constructor.
   *
   * @param $id
   * @param $title
   * @param $tab
   * @param $callback
   */
  public function __construct($id, $title, $tab, $callback) {
    $this->id = $id;
    $this->title = $title;
    $this->tab = $tab;
    $this->callback = $callback;
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
  public function getCallback() {
    return $this->callback;
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

  public function hasCallback() {
    return !is_null($this->callback);
  }
  /**
   * Return section render array.
   *
   * @return array
   *   Return section render array.
   */
  public function getRenderArray() {
    return [
      '#theme' => 'adminic_toolbar_section',
      '#title' => $this->getTitle(),
      '#links' => $this->getLinks(),
    ];
  }

}