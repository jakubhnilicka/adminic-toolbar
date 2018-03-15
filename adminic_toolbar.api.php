<?php

/**
 * @file
 * Adminic Toolbar API.
 */

/**
 * Alter widgets for toolbar.
 *
 * @param array $configWidgets
 *   Config widgets array.
 */
function hook_toolbar_config_widgets_alter(array &$configWidgets) {
  unset($configWidgets['general']);
}

/**
 * Alter tabs for toolbar.
 *
 * @param array $configTabs
 *   Config tabs array.
 */
function hook_toolbar_config_tabs_alter(array &$configTabs) {
  unset($configTabs['media']);
}

/**
 * Alter links for toolbar.
 *
 * @param array $configLinks
 *   Config links array.
 */
function hook_toolbar_config_links_alter(array &$configLinks) {
  unset($configLinks['content.default.system.admin_content']);
}
