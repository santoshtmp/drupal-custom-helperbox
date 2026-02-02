<?php

namespace Drupal\helperbox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class HelperboxSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['helperbox.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helperbox_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('helperbox.settings');

    // $form['enable_helperbox'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Enable Helperbox'),
    //   '#default_value' => $config->get('enable_helperbox'),
    // ];

    $form['enable_media_custom_thumbnail'] = array(
      '#type' => 'select',
      '#title' => $this->t('Enable Medai custom thumbnail'),
      '#options' => [
        false => $this->t('No'),
        true => $this->t('Yes'),
      ],
      '#default_value' => $config->get('enable_media_custom_thumbnail'),
      '#description' => $this->t(
        'Enable custom thumbnails for media items. When enabled, a custom thumbnail can be applied to media types that include a field named <code>field_custom_thumbnail</code>. You can add this field from <strong>Structure → Media types → Media (Remote video / Document) → Manage fields</strong>.'
      ),

    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('helperbox.settings')
      ->set('enable_helperbox', $form_state->getValue('enable_helperbox'))
      ->set('enable_media_custom_thumbnail', $form_state->getValue('enable_media_custom_thumbnail'))
      ->save();
  }
}
