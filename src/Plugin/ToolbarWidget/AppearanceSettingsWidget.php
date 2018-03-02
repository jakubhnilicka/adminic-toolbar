<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarWidget;

use Drupal\adminic_toolbar\ToolbarWidgetPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;

/**
 * Appearance Settings Widget.
 *
 * @ToolbarWidgetPlugin(
 *   id = "appearance_settings",
 *   name = @Translation("Appearance Settings Widget"),
 * )
 */
class AppearanceSettingsWidget extends PluginBase implements ToolbarWidgetPluginInterface {

  public function getRenderArray() {
    /** @var \Drupal\Core\Routing\CurrentRouteMatch $currentRoute */
    $currentRoute = \Drupal::service('current_route_match');
    $currentRouteName = $currentRoute->getRouteName();
    $currentRouteParameterTheme = $currentRoute->getParameter('theme');
    $themeHandler = \Drupal::service('theme_handler');
    $themes = $themeHandler->listInfo();

    $links = [];

    $link_options = [];
    if ($currentRouteName == 'system.theme_settings') {
      $link_options = [
        'attributes' => [
          'class' => [
            'active',
          ],
        ],
      ];
    }
    $globalSettingsUrl = Url::fromRoute('system.theme_settings');
    $globalSettingsUrl->setOptions($link_options);
    $links[] = Link::fromTextAndUrl(t('Global settings'), $globalSettingsUrl);

    foreach ($themes as $name => $theme) {
      $info = $theme->info;
      $link_options = [];
      if ($currentRouteName == 'system.theme_settings_theme' && $currentRouteParameterTheme == $name) {
        $link_options = [
          'attributes' => [
            'class' => [
              'active',
            ],
          ],
        ];
      }

      if (!isset($info['hidden']) || $info['hidden'] == FALSE) {
        $url = Url::fromRoute('system.theme_settings_theme', ['theme' => $name]);
        $url->setOptions($link_options);
        $links[] = Link::fromTextAndUrl($info['name'], $url);
      }
    }

    return [
      '#theme' => 'toolbar_section',
      '#title' => t('Settings'),
      '#links' => $links,
    ];
  }

}
