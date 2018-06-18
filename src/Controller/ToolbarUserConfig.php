<?php

namespace Drupal\adminic_toolbar\Controller;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\CronInterface;
use Drupal\Core\Menu\ContextualLinkManager;
use Drupal\Core\Menu\LocalActionManager;
use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Core\Menu\MenuLinkManager;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ToolbarUserConfig.
 *
 * @package Drupal\adminic_toolbar\Controller
 */
class ToolbarUserConfig extends ControllerBase {

  /**
   * Toolbar configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $toolbarConfiguration;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Private temp store.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  private $privateTempStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(Config $toolbarConfiguration, RequestStack $requestStack, PrivateTempStoreFactory $privateTempStore) {
    $this->toolbarConfiguration = $toolbarConfiguration;
    $this->requestStack = $requestStack;
    $this->privateTempStore = $privateTempStore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $toolbarConfiguration = $container->get('config.factory')->getEditable('adminic_toolbar.configuration');
    $requestStack = $container->get('request_stack');
    $privateTempStore = $container->get('user.private_tempstore');
    return new static(
      $toolbarConfiguration,
      $requestStack,
      $privateTempStore
    );
  }

  /**
   * Use preset
   *
   * @param string $preset
   *   Preset name.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\user\TempStoreException
   */
  public function usePreset($preset) {
    $tempStore = $this->privateTempStore->get('adminic_toolbar');
    // TODO: Validate if user can use preset before save.
    $tempStore->set('adminic_toolbar_preset', $preset);
    drupal_set_message(t('Using %preset toolbar preset.', ['%preset' => $preset]));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Reload the previous page.
   */
  public function reloadPage() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->server->get('HTTP_REFERER')) {
      return $request->server->get('HTTP_REFERER');
    }

    return '/';
  }

}
