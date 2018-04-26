<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarPlugin;

use Drupal\adminic_toolbar\ToolbarPluginInterface;
use Drupal\Core\Link;
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
    $content = [];

    $content[] = $this->getWizardrLink();
    $content[] = $this->getToolbarSettingsLink();
    $content[] = $this->getDeveloperLinks();

    if ($content) {
      return [
        '#theme' => 'toolbar_configuration',
        '#content' => $content,
        '#cache' => ['max-age' => 0],
      ];
    }

    return NULL;
  }

  /**
   * Get links for developers.
   *
   * @return array
   *   Return dropdown render array.
   */
  protected function getDeveloperLinks() {
    if (!$this->currentUser->hasPermission('administer site configuration')) {
      return NULL;
    }

    $content = [];

    // Cache.
    $content[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Cache'),
    ];
    $content[] = Link::fromTextAndUrl(t('All'), Url::fromRoute('adminic_toolbar_configuration.cache', ['cache' => 'all']));
    $content[] = Link::fromTextAndUrl(t('CSS and Javascript'), Url::fromRoute('adminic_toolbar_configuration.cache', ['cache' => 'css-js']));
    $content[] = Link::fromTextAndUrl(t('Plugins'), Url::fromRoute('adminic_toolbar_configuration.cache', ['cache' => 'plugins']));
    $content[] = Link::fromTextAndUrl(t('Render'), Url::fromRoute('adminic_toolbar_configuration.cache', ['cache' => 'render']));
    $content[] = Link::fromTextAndUrl(t('Routing and links'), Url::fromRoute('adminic_toolbar_configuration.cache', ['cache' => 'routing']));
    $content[] = Link::fromTextAndUrl(t('Static'), Url::fromRoute('adminic_toolbar_configuration.cache', ['cache' => 'static']));
    $content[] = Link::fromTextAndUrl(t('Views'), Url::fromRoute('adminic_toolbar_configuration.cache', ['cache' => 'views']));

    // Cron.
    $content[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Cron'),
    ];
    $cronUrl = Url::fromRoute('adminic_toolbar_configuration.cron');
    $content[] = Link::fromTextAndUrl(t('Run cron'), $cronUrl);

    // Updates.
    $content[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Updates'),
    ];
    $cronUrl = Url::fromRoute('adminic_toolbar_configuration.update');
    $content[] = Link::fromTextAndUrl(t('Run database updates'), $cronUrl);

    if ($content) {
      return [
        '#theme' => 'drd',
        '#trigger_content' => '<i class="ico ico--refresh"></i>',
        '#content' => $content,
      ];
    }

    return NULL;

  }

  /**
   * Get toolbar settings link.
   *
   * @return array
   *   Return link render array.
   */
  private function getToolbarSettingsLink() {
    if (!$this->currentUser->hasPermission('can configure adminic toolbar')) {
      return NULL;
    }

    return [
      '#type' => 'link',
      '#title' => Markup::create('<i class="ico ico--configuration"></i>'),
      '#url' => Url::fromRoute('adminic_toolbar_configuration.form'),
      '#attributes' => [
        'class' => [
          'toolbar-configuration',
        ],
      ],
    ];
  }

  /**
   * Get toolbar wizardr link.
   *
   * @return array
   *   Return link render array.
   */
  private function getWizardrLink() {
    if (!$this->currentUser->hasPermission('can configure adminic toolbar')) {
      return NULL;
    }

    return [
      '#type' => 'link',
      '#title' => Markup::create('<i class="ico ico--info"></i>'),
      '#url' => Url::fromRoute('adminic_toolbar_configuration.form'),
      '#attributes' => [
        'class' => [
          'toolbar-info',
        ],
      ],
    ];
  }

}
