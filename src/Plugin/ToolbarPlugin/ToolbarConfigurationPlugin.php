<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarPlugin;

use Drupal\adminic_toolbar\ToolbarConfigDiscovery;
use Drupal\adminic_toolbar\ToolbarPluginInterface;
use Drupal\Core\Config\Config;
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
   * Toolbar configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $toolbarConfiguration;

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
   * @param \Drupal\Core\Config\Config $toolbarConfiguration
   *   Toolbar configuration.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, AccountInterface $currentUser, Config $toolbarConfiguration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
    $this->toolbarConfiguration = $toolbarConfiguration;
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
    $toolbarConfiguration = $container->get('config.factory')->getEditable('adminic_toolbar.configuration');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $currentUser,
      $toolbarConfiguration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderArray() {
    $content = [];

    if ($this->currentUser->hasPermission('can use route info button')) {
      $content[] = $this->getWizardrLink();
    }
    if ($this->currentUser->hasPermission('can configure adminic toolbar')) {
      $content[] = $this->getToolbarSettingsLink();
    }
    if ($this->currentUser->hasPermission('can use developer links')) {
      $content[] = $this->getDeveloperLinks();
    }
    $content[] = $this->getPresetsLinks();

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

  /**
   * Get links for developers.
   *
   * @return array
   *   Return dropdown render array.
   */
  protected function getPresetsLinks() {
    $roles = $this->currentUser->getRoles();
    $presetsConfiguration = $this->toolbarConfiguration->get('adminic_toolbar_presets');

    if (!$presetsConfiguration) {
      return NULL;
    }

    $availablePresets = $this->configuration['presets'];

    $filteredPresets = array_filter($presetsConfiguration, function ($presetKey) use ($roles) {
      return in_array($presetKey, $roles);
    }, ARRAY_FILTER_USE_KEY);


    $content = [];
    $availablePresetsForUser = [];

    foreach ($filteredPresets as $preset) {
      $presets = array_filter($preset, function ($item) {
        return $item;
      });
      $availablePresetsForUser[] = $presets;
    }
    $availablePresetsForUser = array_merge(...$availablePresetsForUser);
    $activePreset = $this->configuration['active_preset'];
    foreach ($availablePresetsForUser as $presetIndex => $preset) {
      $presetTitle = $availablePresets[$presetIndex];
      if ($activePreset === $presetIndex) {
        $content[] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => t('%presetTitle% (active)', ['%presetTitle%' => $presetTitle]),
        ];
      }
      else {
        $content[] = Link::fromTextAndUrl($presetTitle, Url::fromRoute('adminic_toolbar_configuration.use_preset', ['preset' => $presetIndex]));
      }
    }

    if ($content) {
      return [
        '#theme' => 'drd',
        '#trigger_content' => '<i class="ico ico--preset"></i>',
        '#content' => $content,
      ];
    }

    return NULL;

  }

}
