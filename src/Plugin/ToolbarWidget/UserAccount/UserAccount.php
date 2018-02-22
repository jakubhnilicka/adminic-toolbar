<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarWidget\UserAccount;

use Drupal\adminic_toolbar\ToolbarWidgetPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;

/**
 * Class UserAccountToolbarWidget.
 *
 * @ToolbarWidgetPlugin(
 *   id = "user_account",
 *   name = @Translation("User Account Widget"),
 * )
 */
class UserAccount extends PluginBase implements ToolbarWidgetPluginInterface {

  public function getRenderArray() {
    /** @var \Drupal\Core\Session\AccountProxy $current_user */
    $current_user = \Drupal::currentUser();

    $dropdownContent = [];

    $profileUrl = Url::fromRoute('user.page');
    $dropdownContent[] = Link::fromTextAndUrl(t('Profile'), $profileUrl);

    $editUrl = Url::fromRoute('entity.user.edit_form', ['user' => $current_user->id()]);
    $dropdownContent[] = Link::fromTextAndUrl(t('Edit'), $editUrl);

    $logoutUrl = Url::fromRoute('user.logout');
    $dropdownContent[] = Link::fromTextAndUrl(t('Log out'), $logoutUrl);

    $dropdown = [
      '#theme' => 'drd',
      '#trigger_content' => '...',
      '#content' => $dropdownContent,
    ];

    return [
      '#theme' => 'user_account',
      '#avatar' => NULL,
      '#dropdown' => $dropdown,
    ];
  }

}
