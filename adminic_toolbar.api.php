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
function hook_toolbar_primary_sections_alter(array &$configPrimarySections) {
  unset($configPrimarySections['general']);
}

/**
 * Alter widgets for toolbar.
 *
 * @param array $configWidgets
 *   Config widgets array.
 */
function hook_toolbar_secondary_sections_alter(array &$configSecondarySections) {
  unset($configSecondarySections['content.default']);
}

/**
 * Alter tabs for toolbar.
 *
 * @param array $configTabs
 *   Config tabs array.
 */
function hook_toolbar_primary_sections_tabs_alter(array &$configPrimarySectionsTabs) {
  unset($configPrimarySectionsTabs['media']);
}

/**
 * Alter links for toolbar.
 *
 * @param array $configLinks
 *   Config links array.
 */
function hook_toolbar_secondary_sections_links_alter(array &$configSecondarySectionsLinks) {
  unset($configSecondarySectionsLinks['content.default.system.admin_content']);
}
