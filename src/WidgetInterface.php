<?php

namespace Drupal\adminic_toolbar;

interface WidgetInterface {

  /**
   * Get render array for widget.
   *
   * @return array
   */
  public static function getRenderArray();
}