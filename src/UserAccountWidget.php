<?php

namespace Drupal\adminic_toolbar;

class UserAccountWidget implements WidgetInterface {

  public static function getRenderArray() {
    /** @var \Drupal\Core\Session\AccountProxy $current_user */
    $current_user = \Drupal::currentUser();
    $name = $current_user->getAccountName();
    $profile_url = sprintf('/user/%d', $current_user->id());
    $edit_url = sprintf('/user/%d/edit', $current_user->id());
    /** @var \Drupal\user\Entity\User $user */
    //$user = User::load($current_user);
    //$image = $user->get('user_picture');
    return [
      '#theme' => 'user_account',
      '#name' => $name,
      '#edit_url' => $edit_url,
      '#profile_url' => $profile_url,
      '#logout_url' => '/user/logout',
    ];
  }

}