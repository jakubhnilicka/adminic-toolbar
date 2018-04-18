<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarPlugin;

use Drupal\adminic_toolbar\ToolbarPluginInterface;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Appearance Settings Widget.
 *
 * @ToolbarPlugin(
 *   id = "appearance_settings",
 *   name = @Translation("Appearance Settings Widget"),
 * )
 */
class AppearanceSettingsPlugin extends PluginBase implements ToolbarPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  private $themeHandler;

  /**
   * AppearanceSettingsWidget constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Current route match.
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   *   Theme handler.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, CurrentRouteMatch $currentRouteMatch, ThemeHandler $themeHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $currentRouteMatch;
    $this->themeHandler = $themeHandler;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $currentRouteMatch = $container->get('current_route_match');
    $themeHandler = $container->get('theme_handler');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $currentRouteMatch,
      $themeHandler
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray() {
    /** @var \Drupal\Core\Routing\CurrentRouteMatch $currentRoute */
    $currentRouteName = $this->currentRouteMatch->getRouteName();
    $currentRouteParameterTheme = $this->currentRouteMatch->getParameter('theme');
    $themes = $this->themeHandler->listInfo();

    $links = [];

    $globalSettingsUrl = Url::fromRoute('system.theme_settings');
    if ($currentRouteName === 'system.theme_settings') {
      $globalSettingsUrl->setOption('attributes', ['class' => ['active']]);
    }

    $links[] = Link::fromTextAndUrl(t('Global settings'), $globalSettingsUrl);

    foreach ($themes as $name => $theme) {
      $info = $theme->info;
      if (!isset($info['hidden']) || $info['hidden'] === FALSE) {
        $url = Url::fromRoute('system.theme_settings_theme', ['theme' => $name]);
        if ($currentRouteName === 'system.theme_settings_theme' && $currentRouteParameterTheme === $name) {
          $url->setOption('attributes', ['class' => ['active']]);
        }
        $links[] = Link::fromTextAndUrl($info['name'], $url);
      }
    }

    return [
      '#theme' => 'toolbar_secondary_section',
      '#title' => t('Settings'),
      '#links' => $links,
    ];
  }

}
