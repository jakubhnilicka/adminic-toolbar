<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarWidgetPluginInterface.php.
 */

/**
 * Interface ToolbarWidgetPluginInterface.
 *
 * @package Drupal\adminic_toolbar
 */
interface ToolbarWidgetPluginInterface {

  /**
   * Get render array for widget.
   *
   * @return array
   *   Return render array of widget.
   */
  public function getRenderArray();

}
