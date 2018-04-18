<?php

namespace Drupal\adminic_toolbar\Form;

use Drupal\adminic_toolbar\ToolbarThemeDiscovery;
use Drupal\Core\Config\Config;
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
   * ToolbarConfigurationForm constructor.
   *
   * @param \Drupal\Core\Config\Config $configuration
   *   Configuration.
   * @param \Drupal\adminic_toolbar\ToolbarThemeDiscovery $themeDiscovery
   *   Theme discovery.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   Theme handler.
   */
  public function __construct(Config $configuration, ToolbarThemeDiscovery $themeDiscovery, ThemeHandlerInterface $themeHandler) {
    $this->configuration = $configuration;
    $this->themeDiscovery = $themeDiscovery;
    $this->themeHandler = $themeHandler;
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
    return new static(
      $configuration,
      $themeDiscovery,
      $themeHandler
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
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $selectedTheme = $this->configuration->get('adminic_toolbar_theme');
    $compactMode = $this->configuration->get('compact_mode');
    $availableThemes = $this->themeDiscovery->getThemes();

    $form['adminic_toolbar_theme'] = [
      '#type' => 'details',
      '#title' => t('Theme'),
      '#description' => t('Select toolbar themes.'),
      '#open' => TRUE,
      '#weight' => 99,
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
        $form['adminic_toolbar_theme'][$name] = [
          '#type' => 'select',
          '#default_value' => $selectedTheme[$name] ?: 'adminic_toolbar/adminic_toolbar.theme.default',
          '#options' => $availableThemes,
          '#title' => $theme->info['name'],
        ];
      }
    }

    $form['adminic_toolbar_settings'] = [
      '#type' => 'details',
      '#title' => t('Settings'),
      '#open' => TRUE,
      '#weight' => 99,
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

    $compactMode = $values['compact_mode'];
    $this->configuration->set('compact_mode', $compactMode)->save();
  }

}
