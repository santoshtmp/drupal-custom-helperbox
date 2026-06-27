<?php

namespace Drupal\helperbox\Plugin\views\filter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter by created published date.
 *
 * @ViewsFilter("helperbox_date_created_selector")
 */
class DateCreatedSelector extends FilterPluginBase {

    /**
     * Views data registration.
     * 
     * NOTE: This must be called from hook_views_data_alter() in your .module file.
     */
    public static function views_data_alter(&$data, $group = '') {
        $group = $group ?: t('HelperBox');

        $entity_type_manager  = \Drupal::entityTypeManager();
        $entity_field_manager = \Drupal::service('entity_field.manager');

        foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $entity_type) {
            if (!$entity_type->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface')) {
                continue;
            }

            $table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
            if (!$table || !isset($data[$table])) {
                continue;
            }

            $base_field_definitions = $entity_field_manager->getBaseFieldDefinitions($entity_type_id);

            if (
                !isset($base_field_definitions['created'])
                || $base_field_definitions['created']->getType() !== 'created'
            ) {
                continue;
            }

            $data[$table]['created_selector'] = [
                'title'  => t('Date Selector (Published date)'),
                'help'   => t('Filter @entity by published (created) date.', ['@entity' => $entity_type_id]),
                'filter' => [
                    'title'  => t('Date Selector (Published date)'),
                    'help'   => t('Filter by selected published date.'),
                    'field'  => 'created',
                    'id'     => 'helperbox_date_created_selector',
                ],
                'group'  => $group,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function defineOptions() {
        $options = parent::defineOptions();
        $options['date_selection_type'] = ['default' => 'date'];

        // Define the custom expose option for CSS classes
        $options['expose']['contains']['field_classes'] = ['default' => ''];

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptionsForm(&$form, FormStateInterface $form_state) {
        // This allows the site builder to choose between Full Date or Year Only.
        $form['date_selection_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Date selection type'),
            '#options' => [
                'date' => $this->t('Full date (YYYY-MM-DD)'),
                'year' => $this->t('Year only'),
            ],
            '#default_value' => $this->options['date_selection_type'] ?? 'date',
            '#weight' => -10,
        ];

        parent::buildOptionsForm($form, $form_state);
    }

    /**
     * Build expose configuration form.
     * {@inheritdoc}
     */
    public function buildExposeForm(&$form, FormStateInterface $form_state) {
        parent::buildExposeForm($form, $form_state);

        // Add the custom textfield for CSS classes
        $form['expose']['field_classes'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Filter field classes'),
            '#default_value' => $this->options['expose']['field_classes'] ?? '',
            '#description' => $this->t('Enter custom CSS classes for the exposed front-end element, separated by spaces.'),
        ];
    }

    /**
     * {@inheritdoc}
     * 
     * FIX: Override this to prevent the base class from rendering an empty 
     * <div class="views-left-30"></div> which breaks the Views UI modal layout.
     */
    public function showOperatorForm(&$form, FormStateInterface $form_state) {
        // Do nothing. We have no operators, so we don't want the left column wrapper.
    }

    /**
     * {@inheritdoc}
     * 
     * FIX: Override this to prevent wrapping the value form in 'views-right-70'.
     * Since we removed the left column, the value form should take 100% width.
     */
    protected function showValueForm(&$form, FormStateInterface $form_state) {
        $this->valueForm($form, $form_state);
        // We intentionally do NOT add the 'views-right-70' wrapper here.
    }


    /**
     * {@inheritdoc}
     */
    public function valueForm(&$form, FormStateInterface $form_state) {
        $value       = is_array($this->value) ? reset($this->value) : ($this->value ?? '');
        $date_selection_type = $this->options['date_selection_type'] ?? 'date';

        // Access the option correctly (remove 'contains') and fix explode arguments
        $field_classes = $this->options['expose']['field_classes'] ?? '';
        $classes = $field_classes ? array_filter(explode(' ', $field_classes)) : [];

        if ($date_selection_type === 'year') {
            $value = ($value == 'All' || $value === '- Any -') ? '' : $value;
            $current_year = (int) date('Y');

            // Using '- Any -' is standard Drupal UX for exposed selects
            $years = ['All' => '- Any -'];
            $start_year = $this->getEarliestYear();

            for ($year = $current_year; $year >= $start_year; $year--) {
                $years[$year] = $year;
            }

            $classes[] = 'helperbox-year-selector';
            $form['value'] = [
                '#type'          => 'select',
                '#title'         => $this->t('Year'),
                '#options'       => $years,
                '#default_value' => $value,
            ];
        } else {
            $classes[] = 'helperbox-date-range-selector';
            $classes[] = 'form-date';
            $form['value'] = [
                '#type'          => 'textfield',
                '#title'         => $this->t('Date'),
                '#default_value' => $value,
            ];
        }

        // Apply the classes to the HTML element's class attribute if exposed
        if ($this->isExposed() && !empty($classes)) {
            $form['value']['#attributes']['class'] = $classes;
        }

        // FIX: CRITICAL - Use a static method for validation instead of a closure!
        // Closures cannot be serialized and will crash Drupal's form caching system.
        $form['value']['#element_validate'] = [
            [static::class, 'validateDateValue'],
        ];

        // Pass the selection type to the validator so it knows what to check
        $form['value']['#date_selection_type'] = $date_selection_type;
    }

    /**
     * Static callback for validating the date or year value.
     * (Replaces the anonymous closure to prevent fatal cache errors).
     */
    public static function validateDateValue(&$element, FormStateInterface $form_state, &$complete_form) {
        $val = $element['#value'];
        $date_selection_type = $element['#date_selection_type'] ?? 'date';

        if (empty($val) || strtolower($val) === 'all' || $val === '- Any -') {
            return;
        }

        if ($date_selection_type === 'year') {
            if (!preg_match('/^\d{4}$/', $val)) {
                $form_state->setErrorByName($element['#name'], t('Please select a valid 4-digit year.'));
            }
        } else {
            if (is_string($val) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                $form_state->setErrorByName($element['#name'], t('Please enter a valid date in YYYY-MM-DD format.'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query() {
        $value = is_array($this->value) ? reset($this->value) : ($this->value ?? '');

        // Skip if empty or if the user selected the "Any" option
        if (empty($value) || $value === '- Any -') {
            return;
        }

        $this->ensureMyTable();
        $date_selection_type = $this->options['date_selection_type'] ?? 'date';
        $db_column   = "$this->tableAlias.$this->realField";

        if ($date_selection_type === 'year') {
            if (!preg_match('/^\d{4}$/', $value)) {
                return;
            }
            $start = $this->dateRangeToTimestamp("$value-01-01 00:00:00");
            $end   = $this->dateRangeToTimestamp("$value-12-31 23:59:59");
        } else {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return;
            }
            $start = $this->dateRangeToTimestamp($value . ' 00:00:00');
            $end   = $this->dateRangeToTimestamp($value . ' 23:59:59');
        }

        // dateRangeToTimestamp returns null on failure
        if ($start !== null && $end !== null) {
            $this->query->addWhere($this->options['group'], $db_column, [$start, $end], 'BETWEEN');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function canExpose() {
        return TRUE;
    }

    /**
     * Finds the earliest year present in the underlying column.
     */
    protected function getEarliestYear(): int {
        $current_year = (int) date('Y');
        try {
            $query = \Drupal::database()->select($this->table, 't');

            $schema = \Drupal::database()->schema();
            if ($schema->fieldExists($this->table, 'status')) {
                $query->condition('t.status', 1);
            }

            $filters = $this->view->display_handler->getHandlers('filter');
            if (isset($filters['type']) && !empty($filters['type']->value)) {
                $bundles = array_values(array_filter($filters['type']->value, fn($v) => $v !== 'All' && $v !== '0'));
                if (!empty($bundles)) {
                    $query->condition('t.type', $bundles, 'IN');
                }
            }

            $query->addExpression("MIN(t.{$this->realField})", 'min_value');
            $min_value = $query->execute()->fetchField();

            if (empty($min_value)) {
                return $current_year - 10;
            }

            // Convert UTC timestamp to a Date String using the SITE timezone
            $site_timezone_name = \Drupal::config('system.date')->get('timezone.default') ?: date_default_timezone_get();
            $site_timezone = new \DateTimeZone($site_timezone_name);

            // Create a DateTime object from the UTC timestamp
            $date_obj_utc = new \DateTime('@' . $min_value);
            // Set it to the site's timezone so we get the "local" year/month/day
            $date_obj_utc->setTimezone($site_timezone);

            // Get the date string in the site's timezone
            return (int) $date_obj_utc->format('Y');
        } catch (\Exception $e) {
            \Drupal::logger('helperbox')->error('getEarliestYear failed on table @table: @message', [
                '@table' => $this->table,
                '@message' => $e->getMessage(),
            ]);
            return $current_year - 10;
        }
    }

    /**
     * Converts a site-timezone datetime string into a UTC Unix timestamp.
     */
    protected function dateRangeToTimestamp(string $datetime): ?int {
        try {
            if (empty($datetime)) {
                return null;
            }

            // Convert UTC timestamp to a Date String using the SITE timezone
            $site_timezone_name = \Drupal::config('system.date')->get('timezone.default') ?: date_default_timezone_get();
            $site_timezone = new \DateTimeZone($site_timezone_name);

            $date = DrupalDateTime::createFromFormat('Y-m-d H:i:s', $datetime, $site_timezone);

            if (!$date || $date->hasErrors()) {
                return null;
            }

            return (int) $date->getTimestamp();
        } catch (\Throwable $th) {
            //throw $th;
            return null;
        }
    }
}
