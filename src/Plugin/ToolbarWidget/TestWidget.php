<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarWidget;

use Drupal\adminic_toolbar\ToolbarWidgetPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;

/**
 * Class Test Widget.
 *
 * @ToolbarWidgetPlugin(
 *   id = "test_widget",
 *   name = @Translation("Test Widget Widget"),
 * )
 */
class TestWidget extends PluginBase implements ToolbarWidgetPluginInterface {

  public function getRenderArray() {
    return [
      '#markup' => 'Test Widget'
    ];
  }

}
