<?php

namespace Drupal\helperbox\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter by date.
 *
 * Adds a "Date filter mode" setting both in the admin options form and in the
 * expose form. The mode controls which DB column(s) are compared in query():
 *
 *
 * @ViewsFilter("helperbox_date_selector")
 */
class DateSelector extends FilterPluginBase {

    public $no_operator = TRUE;

    /**
     * Views data registration
     * 
     * Registers this filter for every date field found on any entity type.
     */
    public static function views_data_alter($data, $group = '') {
        $group = $group ?: t('HelperBox');

        // Load all date field storages across all entity types.
        $field_storages = \Drupal::entityTypeManager()
            ->getStorage('field_storage_config')
            ->loadByProperties(['type' => 'datetime']);

        // Run this in devel/php or drush php-eval to see all date-related field types:
        // $all = \Drupal::entityTypeManager()
        //     ->getStorage('field_storage_config')
        //     ->loadMultiple();

        // foreach ($all as $field) {
        //     if (str_contains($field->getType(), 'date')) {
        //         dump($field->getName() . ' => ' . $field->getType());
        //     }
        // }

        foreach ($field_storages as $field_storage) {
            $entity_type_id = $field_storage->getTargetEntityTypeId();
            $field_name     = $field_storage->getName();

            // Build the table name: e.g. node__field_date_range.
            $table_name = $entity_type_id . '__' . $field_name;

            // Skip if this table isn't registered in views data.
            if (!isset($data[$table_name])) {
                continue;
            }

            // Register the filter for this date field.
            // Key is unique per field to avoid collisions.
            $data[$table_name][$field_name . '_selector'] = [
                'title'  => t('Date Selector (@field)', ['@field' => $field_name]),
                'help'   => t('Filter @entity by date selector.', [
                    '@entity' => $entity_type_id,
                ]),
                'filter' => [
                    'title'  => t('Date Selector (@field)', ['@field' => $field_name]),
                    'help'   => t('Filter by selected date.'),
                    'field'  => $field_name . '_value', // Sets $this->realField in the plugin.
                    'id'     => 'helperbox_date_selector',
                ],
                'group'  => $group,
            ];
        }
        return $data;
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
        $form['value'] = [
            '#type'          => 'textfield', //'date',
            '#title'         => $this->t('Date'),
            '#default_value' => $value,
            '#attributes'    => [
                'class' => ['helperbox-date-range-selector', 'form-date'],
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

        $date_value      = "$this->tableAlias.{$field_name}_value";

        $this->query->addWhere($this->options['group'], $date_value, $value, '=');


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
