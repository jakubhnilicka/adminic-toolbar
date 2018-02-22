<?php

use Drupal\node\NodeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;

/**
 * @addtogroup hooks
 */

/**
 * Alter links for toolbar
 *
 * @param array $configLinks
 *
 * @return array
 */
function hook_toolbar_config_links(array $configLinks) {
  return $configLinks;
}