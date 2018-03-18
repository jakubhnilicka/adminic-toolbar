<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarPlugin;

use Drupal\adminic_toolbar\ToolbarPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PageInfoWidget.
 *
 * @ToolbarPlugin(
 *   id = "page_info",
 *   name = @Translation("Page Info Widget"),
 * )
 */
class PageInfoPlugin extends PluginBase implements ToolbarPluginInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * AppearanceSettingsWidget constructor.
   * @param $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, AccountProxyInterface $currentUser, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
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
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $currentUser = $container->get('current_user');
    $currentPageRoute = $container->get('current_route_match');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $currentUser,
      $currentPageRoute
    );
  }

  public function getRenderArray() {
    $dropdownContent = [];

    $routeName = $this->currentRouteMatch->getRouteName();
    $routeParameters = $this->currentRouteMatch->getParameters();

    $dropdownContent[] = [
      '#type' => 'inline_template',
      '#template' => "<span>Route name</span>: {{ route_name }}<br/>",
      '#context' => [
        'route_name' => $routeName,
      ],
    ];

    $routeParams = [];
    $params = $routeParameters->all();
    foreach ($params as $key => $parameter) {
      $routeParams[] = $key;
    }

    if ($routeParams) {
      $dropdownContent[] = [
        '#type' => 'inline_template',
        '#template' => "<span>Route parameters</span>: {{ route_parameters }}<br/>",
        '#context' => [
          'route_parameters' => implode(', ', $routeParams),
        ],
      ];
    }

    $dropdown = [
      '#theme' => 'drd',
      '#trigger_content' => 'I',
      '#content' => $dropdownContent,
    ];

    return [
      '#theme' => 'page_info',
      '#dropdown' => $dropdown,
    ];
  }

}
