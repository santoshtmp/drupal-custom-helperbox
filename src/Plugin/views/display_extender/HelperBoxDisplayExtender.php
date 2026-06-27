<?php

namespace Drupal\helperbox\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Helper Box settings for Views Page displays.
 *
 * @ViewsDisplayExtender(
 *   id = "helperbox_display_extender",
 *   title = @Translation("Helper Box Settings"),
 *   help = @Translation("Additional Helper Box settings.")
 * )
 */
class HelperBoxDisplayExtender extends DisplayExtenderPluginBase {

    /**
     * {@inheritdoc}
     */
    protected function defineOptions() {
        $options = parent::defineOptions();
        $options['helperbox_enable_default_banner'] = ['default' => false];
        $options['helperbox_banner_block_content_id'] = ['default' => ''];

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function optionsSummary(&$categories, &$options) {
        parent::optionsSummary($categories, $options);

        if ($this->displayHandler->getPluginId() === 'page' && isset($categories['other'])) {

            $options['helperbox_settings'] = [
                'category' => 'other',
                'title' => $this->t('Helper Box'),
                'value' => 'Settings',
                'desc' => $this->t('Configure the operational settings for the layout helper box.'),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptionsForm(&$form, FormStateInterface $form_state) {
        parent::buildOptionsForm($form, $form_state);

        if ($form_state->get('section') === 'helperbox_settings') {
            $form['#title'] .= $this->t('Helper Box Settings');
            $form['helperbox_enable_default_banner'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Enable helperbox default banner block'),
                '#default_value' => $this->options['helperbox_enable_default_banner'] ?? false,
                '#description' => $this->t('Use the default banner block for this View display.'),
            ];

            $form['helperbox_banner_block_content_id'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Helperbox banner block from block content ID'),
                '#default_value' => $this->options['helperbox_banner_block_content_id'] ?? '',
                '#description' => $this->t('Content block ID. Used only when the default banner block is disabled. Example: 34.'),
                '#states' => [
                    'visible' => [
                        ':input[name="helperbox_enable_default_banner"]' => [
                            'checked' => FALSE,
                        ],
                    ],
                ],
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitOptionsForm(&$form, FormStateInterface $form_state) {
        parent::submitOptionsForm($form, $form_state);

        if ($form_state->get('section') === 'helperbox_settings') {
            $this->options['helperbox_enable_default_banner'] = trim($form_state->getValue('helperbox_enable_default_banner', false));
            $this->options['helperbox_banner_block_content_id'] = trim($form_state->getValue('helperbox_banner_block_content_id', ''));
        }
    }
}
