<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarPlugin;

use Drupal\adminic_toolbar\ToolbarPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ToolbarConfigurationPlugin.
 *
 * @ToolbarPlugin(
 *   id = "toolbar_configuration",
 *   name = @Translation("Toolbar Configuration Plugin"),
 * )
 */
class ToolbarConfigurationPlugin extends PluginBase implements ToolbarPluginInterface, ContainerFactoryPluginInterface {

  /**
   * A configuration array containing information about the plugin instance.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * ToolbarConfigurationPlugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Current route.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $currentRouteMatch;
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
    $currentPageRoute = $container->get('current_route_match');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $currentPageRoute
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray() {
    $dropdownContent = [];

    $routeName = $this->currentRouteMatch->getRouteName();
    $routeParameters = $this->currentRouteMatch->getParameters();
    $routeParams = [];
    $params = $routeParameters->all();
    foreach ($params as $key => $parameter) {
      $routeParams[] = $key;
    }

    $output[] = "secondary_section_id: 'CHANGE_SECONDARY_SECTION_ID'";
    $output[] = "route_name: '" . $routeName . "'";
    $routeParamsOutput = array_map(function ($parameter) {
      return $parameter . ': CHANGE_THIS';
    }, $routeParams);
    $output[] = 'route_parameters: {' . implode(', ', $routeParamsOutput) . '}';
    $output = '- {' . implode(', ', $output) . '}';

    $dropdownContent[] = [
      '#type' => 'inline_template',
      '#template' => '<span>Link</span>: {{ link }}<br/>',
      '#context' => [
        'link' => $output,
      ],
    ];

    $dropdown = [
      '#theme' => 'drd',
      '#trigger_content' => '',
      '#content' => $dropdownContent,
    ];

    return [
      '#theme' => 'page_info',
      '#dropdown' => $dropdown,
      '#cache' => ['max-age' => 0],
    ];
  }

}
