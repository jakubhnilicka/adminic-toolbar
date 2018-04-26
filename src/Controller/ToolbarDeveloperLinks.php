<?php

namespace Drupal\adminic_toolbar\Controller;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\CronInterface;
use Drupal\Core\Menu\ContextualLinkManager;
use Drupal\Core\Menu\LocalActionManager;
use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Core\Menu\MenuLinkManager;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ToolbarDeveloperLinks.
 *
 * @package Drupal\adminic_toolbar\Controller
 */
class ToolbarDeveloperLinks extends ControllerBase {

  /**
   * A cron instance.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * A menu link manager instance.
   *
   * @var \Drupal\Core\Menu\MenuLinkManager
   */
  protected $menuLinkManager;

  /**
   * A context link manager instance.
   *
   * @var \Drupal\Core\Menu\ContextualLinkManager
   */
  protected $contextualLinkManager;

  /**
   * A local task manager instance.
   *
   * @var \Drupal\Core\Menu\LocalTaskManager
   */
  protected $localTaskLinkManager;

  /**
   * A local action manager instance.
   *
   * @var \Drupal\Core\Menu\LocalActionManager
   */
  protected $localActionLinkManager;

  /**
   * A cache backend interface instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * A date time instance.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * A request stack symfony instance.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * A plugin cache clear instance.
   *
   * @var \Drupal\Core\Plugin\CachedDiscoveryClearerInterface
   */
  protected $pluginCacheClearer;

  /**
   * {@inheritdoc}
   */
  public function __construct(CronInterface $cron,
                              MenuLinkManager $menuLinkManager,
                              ContextualLinkManager $contextualLinkManager,
                              LocalTaskManager $localTaskLinkManager,
                              LocalActionManager $localActionLinkManager,
                              CacheBackendInterface $cacheRender,
                              Time $time,
                              RequestStack $request_stack,
                              CachedDiscoveryClearerInterface $plugin_cache_clearer) {
    $this->cron = $cron;
    $this->menuLinkManager = $menuLinkManager;
    $this->contextualLinkManager = $contextualLinkManager;
    $this->localTaskLinkManager = $localTaskLinkManager;
    $this->localActionLinkManager = $localActionLinkManager;
    $this->cacheRender = $cacheRender;
    $this->time = $time;
    $this->requestStack = $request_stack;
    $this->pluginCacheClearer = $plugin_cache_clearer;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cron'),
      $container->get('plugin.manager.menu.link'),
      $container->get('plugin.manager.menu.contextual_link'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('plugin.manager.menu.local_action'),
      $container->get('cache.render'),
      $container->get('datetime.time'),
      $container->get('request_stack'),
      $container->get('plugin.cache_clearer')
    );
  }

  /**
   * Clear caches.
   *
   * @param string $cache
   *   Cache part.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   *
   * @throws \InvalidArgumentException
   */
  public function cache($cache = 'all') {
    switch ($cache) {
      case 'css-js':
        \Drupal::service('asset.css.collection_optimizer')->deleteAll();
        \Drupal::service('asset.js.collection_optimizer')->deleteAll();
        $this->state()
          ->set('system.css_js_query_string', base_convert($this->time->getCurrentTime(), 10, 36));
        drupal_set_message($this->t('Javascript and css caches cleared.'));
        break;

      case 'plugins':
        $this->pluginCacheClearer->clearCachedDefinitions();
        drupal_set_message($this->t('Plugins caches cleared.'));
        break;

      case 'render':
        PhpStorageFactory::get('twig')->deleteAll();
        $this->cacheRender->invalidateAll();
        drupal_set_message($this->t('Render caches cleared.'));
        break;

      case 'routing':
        menu_cache_clear_all();
        $this->menuLinkManager->rebuild();
        $this->contextualLinkManager->clearCachedDefinitions();
        $this->localTaskLinkManager->clearCachedDefinitions();
        $this->localActionLinkManager->clearCachedDefinitions();
        drupal_set_message($this->t('Routing and menu caches cleared.'));
        break;

      case 'static':
        drupal_static_reset();
        drupal_set_message($this->t('Static caches cleared.'));
        break;

      case 'views':
        views_invalidate_cache();
        drupal_set_message($this->t('Views caches cleared.'));
        break;

      default:
        drupal_flush_all_caches();
        drupal_set_message($this->t('All caches cleared.'));
    }
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Run cron.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   *
   * @throws \InvalidArgumentException
   */
  public function cron() {
    $this->cron->run();
    drupal_set_message($this->t('Cron ran successfully.'));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Run updates.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   *
   * @throws \InvalidArgumentException
   */
  public function update() {
    return new RedirectResponse('/update.php');
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
