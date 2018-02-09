<?php

namespace Drupal\adminic_toolbar;

interface ToolbarWidgetPluginInterface {

  /**
   * Get render array for widget.
   *
   * @return array
   */
  public function getRenderArray();
}