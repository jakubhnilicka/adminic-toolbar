<?php

/**
 * @addtogroup hooks
 */

/**
 * Alter links for toolbar
 *
 * @param array $configLinks
 */
function hook_toolbar_config_links_alter(&$configLinks) {
  unset($configLinks['content.default.system.admin_content']);
}

/**
 * Alter sections for toolbar
 *
 * @param array $configSections
 */
function hook_toolbar_config_sections_alter(&$configSections) {
  unset($configSections['general']);
}

/**
 * Alter tabs for toolbar
 *
 * @param array $configTabs
 */
function hook_toolbar_config_tabs_alter(&$configTabs) {
  unset($configTabs['media']);
}