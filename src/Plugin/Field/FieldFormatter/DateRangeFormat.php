<?php

namespace Drupal\helperbox\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime_range\DateTimeRangeDisplayOptions;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeCustomFormatter;
use Drupal\helperbox\Trait\ShowDateStatusTrait;

/**
 * Plugin implementation of the 'Custom' formatter for 'daterange' fields.
 *
 * This formatter renders the data range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 */
#[FieldFormatter(
    id: 'helperbox_fieldformat_daterange',
    label: new TranslatableMarkup('HelperBox - Date Range'),
    field_types: [
        'daterange',
    ],
)]
class DateRangeFormat extends DateRangeCustomFormatter {

    use ShowDateStatusTrait;

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings() {
        $make = [
            'showdatestatus' => FALSE,
        ];
        return $make + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = [];
        $separator = $this->getSetting('separator');
        $showdatestatus = $this->getSetting('showdatestatus');

        // Pull field definition outside the loop — it doesn't change per item.
        $field_definition = $items->getFieldDefinition();
        $datetime_type = $field_definition->getFieldStorageDefinition()
            ->getSettings()['datetime_type'] ?? 'datetime';

        foreach ($items as $delta => $item) {

            $has_start = !empty($item->start_date);
            $has_end   = !empty($item->end_date);

            // Skip if neither date is present.
            if (!$has_start && !$has_end) {
                continue;
            }

            $start_date = $has_start ? $item->start_date : NULL;
            $end_date   = $has_end   ? $item->end_date   : NULL;

            $element = [];

            // Add status if enabled.
            if ($showdatestatus) {

                // Delegate to trait — all status logic lives in one place.
                $element['status'] = $this->checkDateStatus(
                    $datetime_type,
                    $start_date,
                    $end_date,
                    $this->startDateIsDisplayed(),
                    $this->endDateIsDisplayed(),
                );
            } else {
                // Start date.
                if ($this->startDateIsDisplayed()) {
                    $element[DateTimeRangeDisplayOptions::StartDate->value] = $this->buildDate($start_date);
                }

                // Separator — only when both dates are present and displayed.
                if ($this->startDateIsDisplayed() && $this->endDateIsDisplayed()) {
                    $element['separator'] = [
                        '#plain_text' => ' ' . $separator . ' ',
                    ];
                }

                // End date.
                if ($this->endDateIsDisplayed()) {
                    $element[DateTimeRangeDisplayOptions::EndDate->value] = $this->buildDate($end_date);
                }
            }
            $elements[$delta] = $element;
        }

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        $form = parent::settingsForm($form, $form_state);

        $form['showdatestatus'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Show As Date Status (Upcoming, Ongoing, Past)'),
            '#default_value' => $this->getSetting('showdatestatus'),
            '#description' => $this->t('When enabled, this will display the status of the date range (e.g., Upcoming, Ongoing, Past) based on the current date.'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsSummary() {
        $summary = parent::settingsSummary();

        if ($this->getSetting('showdatestatus')) {
            $summary[] = $this->t('Date status enabled (Upcoming / Ongoing / Past)');
        }

        return $summary;
    }
}
