<?php

namespace Drupal\helperbox\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter by date range status: Upcoming, Ongoing, Past.
 *
 * @ViewsFilter("helperbox_date_range_status")
 */
class DateRangeStatus extends FilterPluginBase {

    public $no_operator = TRUE;

    /**
     * Alter the views data to add our custom filter definition.
     *
     * @param array $data
     *   The views data array.
     * @param string $group
     *   The group name for the filter (optional).
     *
     * @return array
     *   The modified views data array.
     */
    public static function views_data_alter($data, $group = '') {
        $group = $group ?: t('HelperBox');

        // Load all daterange field storages across all entity types.
        $field_storages = \Drupal::entityTypeManager()
            ->getStorage('field_storage_config')
            ->loadByProperties(['type' => 'daterange']);

        foreach ($field_storages as $field_storage) {
            $entity_type_id = $field_storage->getTargetEntityTypeId();
            $field_name     = $field_storage->getName();

            // Build the table name: e.g. node__field_date_range.
            $table_name = $entity_type_id . '__' . $field_name;

            // Skip if this table isn't registered in views data.
            if (!isset($data[$table_name])) {
                continue;
            }

            // Get a human-readable group label.
            try {
                $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
                $group       = $group . ' - ' . (string) $entity_type->getLabel();
            } catch (\Exception $e) {
                $group = $group . ' - ' . $entity_type_id;
            }

            // Register the filter for this date range field.
            // Key is unique per field to avoid collisions.
            $data[$table_name][$field_name . '_status'] = [
                'title'  => t('Date Range Status (@field)', ['@field' => $field_name]),
                'help'   => t('Filter @entity by date range status: Upcoming, Ongoing, or Past.', [
                    '@entity' => $entity_type_id,
                ]),
                'filter' => [
                    'title'  => t('Date Range Status (@field)', ['@field' => $field_name]),
                    'help'   => t('Filter by Upcoming, Ongoing, or Past.'),
                    'field'  => $field_name . '_value', // Sets $this->realField in the plugin.
                    'id'     => 'helperbox_date_range_status',
                ],
                'group'  => $group,
            ];
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function adminSummary() {
        $value = is_array($this->value) ? reset($this->value) : ($this->value ?? '');
        return $this->t('Status: @value', ['@value' => $value ?: '- Any -']);
    }

    /**
     * {@inheritdoc}
     */
    protected function defineOptions() {
        $options = parent::defineOptions();
        $options['value']       = ['default' => ''];
        $options['widget_type'] = ['default' => 'radios'];
        return $options;
    }

    /**
     * {@inheritdoc}
     *
     * Shown when filter is NOT exposed (main config dialog).
     */
    public function buildOptionsForm(&$form, FormStateInterface $form_state) {
        parent::buildOptionsForm($form, $form_state);

        $form['widget_type'] = [
            '#type'          => 'radios',
            '#title'         => $this->t('Widget type'),
            '#description'   => $this->t('How the exposed filter is rendered.'),
            '#options'       => [
                'radios' => $this->t('Radio buttons'),
                'select' => $this->t('Select list'),
            ],
            '#default_value' => $this->options['widget_type'] ?? 'radios',
            '#weight'        => -10,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function valueForm(&$form, FormStateInterface $form_state) {
        $status_options = [
            'All'      => $this->t('- Any -'),
            'upcoming' => $this->t('Upcoming'),
            'ongoing'  => $this->t('Ongoing'),
            'past'     => $this->t('Past'),
        ];

        $widget_type = $this->options['widget_type'] ?? 'radios';

        $form['value'] = [
            '#type'          => $widget_type,
            '#title'         => $this->t('Status'),
            '#options'       => $status_options,
            '#default_value' => is_array($this->value) ? reset($this->value) : ($this->value ?? 'All'),
        ];

    }

    /**
     * {@inheritdoc}
     *
     * Replaces the element in the frontend exposed form with the correct widget type.
     * Parent always renders a <select> — we override it here.
     */
    public function buildExposedForm(&$form, FormStateInterface $form_state) {
        parent::buildExposedForm($form, $form_state);

        $identifier  = $this->options['expose']['identifier'] ?? '';
        $widget_type = $this->options['widget_type'] ?? 'radios';

        if ($identifier && isset($form[$identifier])) {
            $existing = $form[$identifier];
            $form[$identifier] = [
                '#type'          => $widget_type,
                '#title'         => $existing['#title'] ?? '',
                '#options'       => $existing['#options'] ?? [],
                '#default_value' => $existing['#default_value'] ?? 'All',
                '#weight'        => $existing['#weight'] ?? 0,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acceptExposedInput($input) {
        $result = parent::acceptExposedInput($input);
        if (is_array($this->value)) {
            $this->value = reset($this->value);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function query() {
        $value = is_array($this->value) ? reset($this->value) : ($this->value ?? '');
        if (empty($value) || $value === 'All') {
            return;
        }

        // Ensure the field table is in the query and get its alias.
        $this->ensureMyTable();
        
        // Strip the trailing _value suffix to get the base field name.
        $field_name = preg_replace('/_value$/', '', $this->realField);

        $start      = "$this->tableAlias.{$field_name}_value";
        $end        = "$this->tableAlias.{$field_name}_end_value";
        $now        = date('Y-m-d', \Drupal::time()->getRequestTime());

        switch ($value) {
            case 'upcoming':
                // Events that start in the future.
                $this->query->addWhere($this->options['group'], $start, $now, '>');
                break;

            case 'ongoing':
                // Start date/time <= now AND End date/time >= now.
                $this->query->addWhere($this->options['group'], $start, $now, '<=');
                $this->query->addWhere($this->options['group'], $end, $now, '>=');
                break;
            case 'past':
                // Events that have already ended.
                $this->query->addWhere($this->options['group'], $end, $now, '<');
                break;
        }

        // Get bundle from the view's content type filter if present.
        $filters = $this->view->display_handler->getHandlers('filter');
        if (!empty($filters['type']->value)) {
            $bundles = array_filter($filters['type']->value, fn($v) => $v !== 'All');
            if ($bundle = reset($bundles)) {
                $this->query->addWhere($this->options['group'], "$this->tableAlias.bundle", $bundle, '=');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validate() {
        $errors = parent::validate();
        $value  = is_array($this->value) ? reset($this->value) : ($this->value ?? '');
        if (!empty($value) && !in_array($value, ['', 'All', 'upcoming', 'ongoing', 'past'])) {
            $errors[] = $this->t('The date status "@value" is not valid.', ['@value' => $value]);
        }
        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function canExpose() {
        return TRUE;
    }
}
