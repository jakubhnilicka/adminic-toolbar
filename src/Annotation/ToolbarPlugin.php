<?php

namespace Drupal\adminic_toolbar\Annotation;

/**
 * @file
 * ToolbarPlugin.php.
 */

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Toolbar Plugin item annotation object.
 *
 * @Annotation
 */
class ToolbarPlugin extends Plugin {

  public $id;
  public $name;

}
