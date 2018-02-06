<?php

namespace Drupal\adminic_toolbar\Components;

class Section {

  private $title;

  private $links = NULL;

  private $id;

  private $tab;

  /**
   * Section constructor.
   *
   * @param $id
   * @param $title
   * @param $tab
   */
  public function __construct($id, $title, $tab) {
    $this->id = $id;
    $this->title = $title;
    $this->tab = $tab;
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