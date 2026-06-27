<?php

namespace Drupal\helperbox\Trait;

trait ShowDateStatusTrait {

    /**
     * @param string $datetime_type
     * @param \Drupal\Core\Datetime\DrupalDateTime|\DateTimeInterface|\DateTime|null $start_date
     * @param \Drupal\Core\Datetime\DrupalDateTime|\DateTimeInterface|\DateTime|null $end_date
     * @param bool $start_date_displayed
     * @param bool $end_date_displayed
     */
    protected function checkDateStatus(
        string $datetime_type,
        $start_date,
        $end_date,
        bool $start_date_displayed,
        bool $end_date_displayed
    ): array {
        $now_dt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $has_start = $start_date !== NULL;
        $has_end   = $end_date !== NULL;

        $status       = '';
        $status_class = '';

        if ($datetime_type === 'date') {
            // Date-only: compare as Ymd strings to avoid timezone shifts.
            $now_compare   = $now_dt->format('Ymd');
            $start_compare = $has_start ? $start_date->format('Ymd') : NULL;
            $end_compare   = $has_end   ? $end_date->format('Ymd')   : NULL;
        } else {
            // Datetime: compare full Unix timestamps.
            $now_compare   = $now_dt->getTimestamp();
            $start_compare = $has_start ? $start_date->getTimestamp() : NULL;
            $end_compare   = $has_end   ? $end_date->getTimestamp()   : NULL;
        }

        if ($start_date_displayed && $end_date_displayed) {
            // Full range comparison.
            if ($now_compare < $start_compare) {
                $status       = $this->t('Upcoming');
                $status_class = 'upcoming';
            } elseif ($now_compare > $end_compare) {
                $status       = $this->t('Past');
                $status_class = 'past';
            } else {
                $status       = $this->t('Ongoing');
                $status_class = 'ongoing';
            }
        } elseif ($start_date_displayed && $has_start) {
            // Start only.
            if ($now_compare < $start_compare) {
                $status       = $this->t('Upcoming');
                $status_class = 'upcoming';
            } elseif ($now_compare > $start_compare) {
                $status       = $this->t('Past');
                $status_class = 'past';
            } else {
                $status       = $this->t('Ongoing');
                $status_class = 'ongoing';
            }
        } elseif ($end_date_displayed && $has_end) {
            // End only.
            if ($now_compare < $end_compare) {
                $status       = $this->t('Upcoming');
                $status_class = 'upcoming';
            } elseif ($now_compare > $end_compare) {
                $status       = $this->t('Past');
                $status_class = 'past';
            } else {
                $status       = $this->t('Ongoing');
                $status_class = 'ongoing';
            }
        }

        return [
            '#type'       => 'html_tag',
            '#tag'        => 'span',
            '#attributes' => [
                'class' => [
                    'date__status',
                    'date__status--' . $status_class,
                ],
            ],
            '#value'      => $status,
        ];
    }
}
