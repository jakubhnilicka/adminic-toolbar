<?php

namespace Drupal\adminic_toolbar\Plugin\ToolbarWidget;

use Drupal\adminic_toolbar\ToolbarWidgetPluginInterface;
use Drupal\Core\Plugin\PluginBase;

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
    $name = $current_user->getAccountName();
    $profile_url = sprintf('/user/%d', $current_user->id());
    $edit_url = sprintf('/user/%d/edit', $current_user->id());

    return [
      '#theme' => 'user_account',
      '#name' => $name,
      '#edit_url' => $edit_url,
      '#profile_url' => $profile_url,
      '#logout_url' => '/user/logout',
    ];
  }

}
