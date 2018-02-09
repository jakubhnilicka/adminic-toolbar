<?php

namespace Drupal\adminic_toolbar\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Toolbar Widget plugin item annotation object.
 *
 * @Annotation
 */
class ToolbarWidgetPlugin extends Plugin {

  public $id;
  public $name;
}
