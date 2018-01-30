<?php

namespace Drupal\adminic_toolbar\Form;

/**
 * @file
 * Contains \Drupal\adminic_toolbar\Form\CommandForm.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CommandForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'command_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['command_name'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => t('Enter command'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}