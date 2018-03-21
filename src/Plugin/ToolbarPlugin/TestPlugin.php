<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarPlugin;

use Drupal\adminic_toolbar\ToolbarPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;

/**
 * Class Test Widget.
 *
 * @ToolbarPlugin(
 *   id = "test_widget",
 *   name = @Translation("Test Widget Widget"),
 * )
 */
class TestPlugin extends PluginBase implements ToolbarPluginInterface {

  public function getRenderArray() {
    return [
      '#markup' => 'Test Widget'
    ];
  }

}
