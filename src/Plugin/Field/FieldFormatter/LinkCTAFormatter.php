<?php

namespace Drupal\helperbox\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;

/**
 * Plugin implementation of the 'helperbox_fieldformat_cta_link' formatter.
 */
#[FieldFormatter(
    id: 'helperbox_fieldformat_cta_link',
    label: new TranslatableMarkup('HelperBox - CTA Link'),
    field_types: ['link'],
)]
class LinkCTAFormatter extends FormatterBase {

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings() {
        return [
            'cta_type' => 'primary',
        ] + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        $elements = parent::settingsForm($form, $form_state);

        $elements['cta_type'] = [
            '#type' => 'select',
            '#title' => $this->t('CTA Type'),
            '#default_value' => $this->getSetting('cta_type'),
            '#options' => [
                'primary' => $this->t('Primary Button'),
                'secondary' => $this->t('Secondary Button'),
            ],
            '#description' => $this->t('Select the button style.'),
        ];

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsSummary() {
        $summary = parent::settingsSummary();

        $summary[] = $this->t('CTA Type: @type', [
            '@type' => ucfirst($this->getSetting('cta_type')),
        ]);

        return $summary;
    }

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {

        $elements = [];
        $cta_type = $this->getSetting('cta_type');

        foreach ($items as $delta => $item) {
            if (!$item->isEmpty()) {

                $url = Url::fromUri($item->uri, $item->options ?? []);
                $options = $item->options ?? [];

                // Get target from link field options.
                $target = $options['attributes']['target'] ?? NULL;

                // Detect external link.
                $is_external = $url->isExternal();

                // Fallback: if no target set and external → open in new tab.
                if (!$target && $is_external) {
                    $target = '_blank';
                }

                $elements[$delta] = [
                    '#theme' => 'helperbox_add_cta',
                    '#cta_url' => $url->toString(),
                    '#cta_label' => $item->title ?: $item->uri,
                    '#cta_type' => $cta_type,
                    '#cta_target' => $target,
                    '#is_external' => $is_external,
                    '#attributes' => new Attribute(),
                ];
            }
        }

        return $elements;
    }
}
