<?php

namespace Drupal\adminic_toolbar;

/**
 * @file
 * ToolbarPluginPluginInterface.php.
 */

/**
 * Interface ToolbarPluginPluginInterface.
 *
 * @package Drupal\adminic_toolbar
 */
interface ToolbarPluginInterface {

  /**
   * Get render array for widget.
   *
   * @return array
   *   Return render array of widget.
   */
  public function getRenderArray();

}
