<?php

namespace Drupal\helperbox\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter by date range.
 *
 * Adds a "Date filter mode" setting both in the admin options form and in the
 * expose form. The mode controls which DB column(s) are compared in query():
 *
 *   start – field_value = chosen date
 *   end   – field_end_value = chosen date
 *   both  – chosen date must fall within [field_value, field_end_value]
 *
 * @ViewsFilter("helperbox_date_range_selector")
 */
class DateRangeSelector extends FilterPluginBase {

    public $no_operator = TRUE;

    /**
     * Views data registration
     * 
     * Registers this filter for every daterange field found on any entity type.
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

            // // Get a human-readable group label.
            // try {
            //     $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
            //     $group = $group . ' - ' . (string) $entity_type->getLabel();
            // } catch (\Exception $e) {
            //     $group = $group . ' - ' . $entity_type_id;
            // }

            // Register the filter for this date range field.
            // Key is unique per field to avoid collisions.
            $data[$table_name][$field_name . '_selector'] = [
                'title'  => t('Date Range Selector (@field)', ['@field' => $field_name]),
                'help'   => t('Filter @entity by date range selector.', [
                    '@entity' => $entity_type_id,
                ]),
                'filter' => [
                    'title'  => t('Date Range Selector (@field)', ['@field' => $field_name]),
                    'help'   => t('Filter by selected date.'),
                    'field'  => $field_name . '_value', // Sets $this->realField in the plugin.
                    'id'     => 'helperbox_date_range_selector',
                ],
                'group'  => $group,
            ];
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     *
     * Key points from core FilterPluginBase::defineOptions():
     *  - 'expose' is already defined as ['contains' => [...]] there.
     *  - Adding our own key under 'expose.contains' merges cleanly.
     *  - The 'contains' structure is what makes unpackOptions() recurse into it,
     *    so the value survives save/load cycles and the #flatten pre_render in
     *    buildExposeForm() writes it back automatically.
     */
    protected function defineOptions() {
        $options = parent::defineOptions();

        // Non-exposed (admin) mode — stored at top level.
        $options['date_mode'] = ['default' => 'both'];

        // Define the custom expose option for CSS classes
        $options['expose']['contains']['field_classes'] = ['default' => ''];

        return $options;
    }

    /**
     * {@inheritdoc}
     *
     * buildOptionsForm() in FilterPluginBase calls:
     *   showOperatorForm() → showValueForm() → showExposeForm()
     *
     * We add our select before calling parent so it appears above the value
     * date-picker in the dialog.
     */
    public function buildOptionsForm(&$form, FormStateInterface $form_state) {

        $form['date_mode'] = [
            '#type'          => 'select',
            '#title'         => $this->t('Date filter mode'),
            '#description'   => $this->t('Which date column to compare when the filter is <em>not</em> exposed.'),
            '#options'       => $this->dateModeOptions(),
            '#default_value' => $this->options['date_mode'],
        ];

        // Parent renders operator / value / expose sections.
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
        $value = is_array($this->value) ? reset($this->value) : ($this->value ?? '');
        // // Normalize to yyyy-mm-dd regardless of source format.
        // if (!empty($value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        //     $timestamp = strtotime($value);
        //     $value     = $timestamp ? date('Y-m-d', $timestamp) : '';
        // }

        // Access the option correctly (remove 'contains') and fix explode arguments
        $field_classes = $this->options['expose']['field_classes'] ?? '';
        $classes = $field_classes ? array_filter(explode(' ', $field_classes)) : [];
        $classes = array_merge($classes, ['helperbox-date-range-selector', 'form-date']);

        $form['value'] = [
            '#type'          => 'textfield', //'date',
            '#title'         => $this->t('Date'),
            '#default_value' => $value,
            '#attributes'    => [
                'class' => $classes,
            ],
            '#size'          => 20,
            '#placeholder'   => $this->t('YYYY-MM-DD'),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * FilterPluginBase::acceptExposedInput() sets $this->value from the GET
     * parameter and also sets $this->options['exposed'] = TRUE at runtime when
     * the filter is exposed and the user submitted the form.
     *
     * We use $this->isExposed() (which checks $this->options['exposed']) to pick
     * the correct mode setting.
     */
    public function query() {
        $value = is_array($this->value) ? reset($this->value) : ($this->value ?? '');
        if (empty($value)) {
            return;
        }

        // Ensure the field table is in the query and get its alias.
        $this->ensureMyTable();

        // Strip the trailing _value suffix to get the base field name.
        $field_name = preg_replace('/_value$/', '', $this->realField);

        $start      = "$this->tableAlias.{$field_name}_value";
        $end        = "$this->tableAlias.{$field_name}_end_value";
        $mode = $this->options['date_mode'] ?? 'both';

        switch ($mode) {
            case 'start':
                // Only compare start date.
                $this->query->addWhere($this->options['group'], $start, $value, '=');
                break;
            case 'end':
                // Only compare end date.
                $this->query->addWhere($this->options['group'], $end, $value, '=');
                break;
            case 'both':
            default:
                // Date must fall within range.
                $this->query->addWhere($this->options['group'], $start, $value, '<=');
                $this->query->addWhere($this->options['group'], $end, $value, '>=');
                break;
        }

        // Narrow by bundle when a sibling 'type' filter is active.
        $this->applyBundleFilter();
    }

    /**
     * {@inheritdoc}
     */
    public function validate() {
        $errors = parent::validate();
        $value  = is_array($this->value) ? reset($this->value) : ($this->value ?? '');

        if (!empty($value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $errors[] = $this->t(
                'Selected date "@value" is not a valid date (expected YYYY-MM-DD).',
                ['@value' => $value]
            );
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function canExpose() {
        return TRUE;
    }

    /**
     * Shared mode options.
     */
    protected function dateModeOptions(): array {
        return [
            'start' => $this->t('Use start date only'),
            'end'   => $this->t('Use end date only'),
            'both'  => $this->t('Use both (date must fall within range)'),
        ];
    }

    /**
     * Narrows results by bundle when a sibling 'type' filter is active.
     */
    protected function applyBundleFilter(): void {
        $filters = $this->view->display_handler->getHandlers('filter');
        if (
            isset($filters['type']) &&
            !empty($filters['type']->value) &&
            is_array($filters['type']->value)
        ) {
            $bundles = array_filter($filters['type']->value, fn($v) => $v !== 'All');
            if ($bundle = reset($bundles)) {
                $this->query->addWhere(
                    $this->options['group'],
                    "$this->tableAlias.bundle",
                    $bundle,
                    '='
                );
            }
        }
    }
}
