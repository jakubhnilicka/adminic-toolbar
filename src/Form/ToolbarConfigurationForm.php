<?php

namespace Drupal\adminic_toolbar\Form;

use Drupal\adminic_toolbar\ToolbarConfigDiscovery;
use Drupal\adminic_toolbar\ToolbarThemeDiscovery;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ToolbarConfigurationForm.
 *
 * @package Drupal\adminic_toolbar\Form
 */
class ToolbarConfigurationForm extends FormBase {

  /**
   * Configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $configuration;

  /**
   * Theme discovery.
   *
   * @var \Drupal\adminic_toolbar\ToolbarThemeDiscovery
   */
  private $themeDiscovery;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  private $themeHandler;

  /**
   * Config discovery.
   *
   * @var \Drupal\adminic_toolbar\ToolbarConfigDiscovery
   */
  private $configDiscovery;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * ToolbarConfigurationForm constructor.
   *
   * @param \Drupal\Core\Config\Config $configuration
   *   Configuration.
   * @param \Drupal\adminic_toolbar\ToolbarThemeDiscovery $themeDiscovery
   *   Theme discovery.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   Theme handler.
   * @param \Drupal\adminic_toolbar\ToolbarConfigDiscovery $configDiscovery
   *   Config discovery.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity tpe managaer.
   */
  public function __construct(Config $configuration, ToolbarThemeDiscovery $themeDiscovery, ThemeHandlerInterface $themeHandler, ToolbarConfigDiscovery $configDiscovery, EntityTypeManagerInterface $entityTypeManager) {
    $this->configuration = $configuration;
    $this->themeDiscovery = $themeDiscovery;
    $this->themeHandler = $themeHandler;
    $this->configDiscovery = $configDiscovery;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    $configuration = $container->get('config.factory')->getEditable('adminic_toolbar.configuration');
    $themeDiscovery = $container->get('adminic_toolbar.toolbar_theme_discovery');
    $themeHandler = $container->get('theme_handler');
    $configDiscovery = $container->get('adminic_toolbar.discovery_manager');
    $entityTypeManager = $container->get('entity_type.manager');
    return new static(
      $configuration,
      $themeDiscovery,
      $themeHandler,
      $configDiscovery,
      $entityTypeManager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adminic_toolbar_configuration_form';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Discovery\DiscoveryException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $selectedTheme = $this->configuration->get('adminic_toolbar_theme');
    $compactMode = $this->configuration->get('compact_mode');
    $availableThemes = $this->themeDiscovery->getThemes();
    $presetsConfiguration = $this->configuration->get('adminic_toolbar_presets');

    $form['adminic_toolbar_theme'] = [
      '#type' => 'details',
      '#title' => t('Theme'),
      '#description' => t('Select toolbar themes.'),
      '#open' => TRUE,
      '#weight' => 0,
      '#attributes' => [
        'class' => [
          'adminic-toolbar-theme',
          'clearfix',
        ],
      ],
    ];

    $themes = $this->themeHandler->listInfo();
    foreach ($themes as $name => $theme) {
      if (!isset($theme->info['hidden'])) {
        $defaultTheme = 'adminic_toolbar/adminic_toolbar.theme.default';
        if (isset($selectedTheme[$name])) {
          $defaultTheme = $selectedTheme[$name];
        }
        $form['adminic_toolbar_theme'][$name] = [
          '#type' => 'select',
          '#default_value' => $defaultTheme,
          '#options' => $availableThemes,
          '#title' => $theme->info['name'],
        ];
      }
    }

    $form['adminic_toolbar_presets'] = [
      '#type' => 'details',
      '#title' => t('Presets'),
      '#description' => t('Select which presets are available to which roles.'),
      '#open' => TRUE,
      '#weight' => 1,
      '#attributes' => [
        'class' => [
          'adminic-toolbar-presets',
          'clearfix',
        ],
      ],
    ];
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $presets = $this->configDiscovery->getAvailablePresets();
    foreach ($roles as $index => $role) {
      $defaultvalue = $presetsConfiguration[$index] ?? [];
      $defaultvalue = array_filter($defaultvalue, function ($value) {
        return $value;
      });

      $form['adminic_toolbar_presets'][$role->id()] = [
        '#type' => 'checkboxes',
        '#default_value' => array_keys($defaultvalue),
        '#options' => $presets,
        '#title' => $role->label(),
      ];
    }

    $form['adminic_toolbar_settings'] = [
      '#type' => 'details',
      '#title' => t('Settings'),
      '#open' => TRUE,
      '#weight' => 3,
      '#attributes' => [
        'class' => [
          'adminic-toolbar-settings',
          'clearfix',
        ],
      ],
    ];

    $form['adminic_toolbar_settings']['compact_mode'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use compact mode'),
      '#default_value' => $compactMode ?: FALSE,
    );

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );

    $form['#attached']['library'][] = 'adminic_toolbar/adminic_toolbar.configuration_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\ConfigValueException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $themes = $this->themeHandler->listInfo();
    $themeConfiguration = [];
    foreach ($themes as $name => $theme) {
      if (!isset($theme->info['hidden'])) {
        $themeConfiguration[$name] = $values[$name];
      }
    }
    $this->configuration->set('adminic_toolbar_theme', $themeConfiguration)->save();

    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $presetMatrix = [];
    foreach ($roles as $index => $role) {
      $presets = array_map(function ($role) {
        return ($role !== 0) ? TRUE : FALSE;
      }, $values[$index]);
      $presetMatrix[$index] = $presets;
    }
    $this->configuration->set('adminic_toolbar_presets', $presetMatrix)->save();

    $compactMode = $values['compact_mode'];
    $this->configuration->set('compact_mode', $compactMode)->save();
  }

}
