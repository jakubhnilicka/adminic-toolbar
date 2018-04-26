<?php

namespace Drupal\adminic_toolbar;

use Drupal\Core\Discovery\YamlDiscovery;

/**
 * Provides discovery for YAML files within a given set of directories.
 *
 * This overrides the Component file decoding with the Core YAML implementation.
 */
class ToolbarYamlDiscovery extends YamlDiscovery {

  /**
   * Returns an array of file paths, keyed by provider.
   *
   * @return array
   */
  protected function findFiles() {
    $files = [];
    foreach ($this->directories as $provider => $directory) {
      $filesFound = glob($directory . '/' . $provider . '.*.' . $this->name . '.yml');
      foreach ($filesFound as $file) {
        $re = '/.(\w*).toolbar.yml/';
        preg_match_all($re, $file, $matches, PREG_SET_ORDER, 0);
        $matches = reset($matches);
        $set = $matches[1];
        $key = sprintf('%s.%s', $provider, $set);
        $files[$key] = $file;
      }
    }
    return $files;
  }

}
