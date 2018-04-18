<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarPlugin;

use Drupal\adminic_toolbar\ToolbarPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ToolbarUserAccountPlugin.
 *
 * @ToolbarPlugin(
 *   id = "toobar_user_account",
 *   name = @Translation("User Account Widget"),
 * )
 */
class ToolbarUserAccountPlugin extends PluginBase implements ToolbarPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * ToolbarUserAccountPlugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, AccountProxyInterface $currentUser) {
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
    $dropdownContent = [];

    $profileUrl = Url::fromRoute('user.page');
    $dropdownContent[] = Link::fromTextAndUrl(t('Profile'), $profileUrl);

    $editUrl = Url::fromRoute('entity.user.edit_form', ['user' => $this->currentUser->id()]);
    $dropdownContent[] = Link::fromTextAndUrl(t('Edit'), $editUrl);

    $logoutUrl = Url::fromRoute('user.logout');
    $dropdownContent[] = Link::fromTextAndUrl(t('Log out'), $logoutUrl);

    $name = $this->currentUser->getDisplayName();

    $dropdown = [
      '#theme' => 'drd',
      '#trigger_content' => '<i class="ico ico--user"></i>' . '<span>' . $name . '</span>',
      '#content' => $dropdownContent,
    ];

    return [
      '#theme' => 'toolbar_user_account',
      '#dropdown' => $dropdown,
      '#cache' => ['max-age' => 0],
    ];
  }

}
