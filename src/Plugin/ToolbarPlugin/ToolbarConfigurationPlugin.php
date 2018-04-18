<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarPlugin;

use Drupal\adminic_toolbar\ToolbarPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
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
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * ToolbarConfigurationPlugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
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
    $currentUser = $container->get('current_user');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $currentUser
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray() {
    if (!$this->currentUser->hasPermission('can configure adminic toolbar')) {
      return NULL;
    }

    $content = [];

    $content[] = [
      '#type' => 'link',
      '#title' => Markup::create('<i class="ico ico--info"></i>'),
      '#url' => Url::fromRoute('adminic_toolbar_configuration.form'),
      '#attributes' => [
        'class' => [
          'toolbar-info',
        ],
      ],
    ];

    $content[] = [
      '#type' => 'link',
      '#title' => Markup::create('<i class="ico ico--configuration"></i>'),
      '#url' => Url::fromRoute('adminic_toolbar_configuration.form'),
      '#attributes' => [
        'class' => [
          'toolbar-configuration',
        ],
      ],
    ];

    return [
      '#theme' => 'toolbar_configuration',
      '#content' => $content,
      '#cache' => ['max-age' => 0],
    ];
  }

}
