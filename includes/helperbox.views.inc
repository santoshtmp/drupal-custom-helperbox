<?php

/**
 * @file
 * Views hooks for Helperbox module.
 */

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\helperbox\Helper\UtilHelper;

/**
 * Implements hook_views_query_alter().
 *
 * Filter Config Pages views to the current language.
 */
function helperbox_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
    try {
        // Check if the view is for Config Pages.
        if ($view->storage->get('base_table') !== 'config_pages') {
            return;
        }

        // Skip if query is not SQL (unlikely for Config Pages).
        if (!$query instanceof \Drupal\views\Plugin\views\query\Sql) {
            return;
        }

        // Get the current language code.
        $current_lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if (!$current_lang) {
            return;
        }

        // Ensure the query has at least one field selected.
        // This prevents "SELECT FROM" syntax errors when no fields are defined.
        $select_query = $query->query();
        $fields = $select_query->getFields();
        if (empty($fields)) {
            // Add the base table ID field if no fields are present.
            $table_alias = $query->ensureTable('config_pages');
            $query->addField($table_alias, 'id');
        }

        // Build safe LIKE patterns for both JSON and serialized PHP formats.
        // JSON format: {"language":"en",...}
        $like_json = '%"language":"' . $current_lang . '"%';

        // PHP serialized format: s:8:"language";s:2:"en";
        // We use a more flexible pattern that matches any string length.
        $like_php = '%s:' . strlen($current_lang) . ':"' . $current_lang . '"%';

        $expression = "(context LIKE :lang_json OR context LIKE :lang_php)";

        // Add to the default WHERE group (0).
        $query->addWhereExpression(0, $expression, [
            ':lang_json' => $like_json,
            ':lang_php'  => $like_php,
        ]);
    } catch (\Throwable $th) {
        // Log the error using helperbox error logger.
        UtilHelper::helperbox_error_log($th);
        \Drupal::logger('helperbox')->error('Error in helperbox_views_query_alter: @message', ['@message' => $th->getMessage()]);
    }
}

/**
 * Implements hook_views_data_alter().
 */
function helperbox_views_data_alter(array &$data) {
    $data['views']['helperbox_count_node'] = [
        'title' => t('Count node'),
        'help'  => t('Count content type node'),
        'field' => [
            'id' => 'helperbox_count_node',
        ],
        'group' => t('HelperBox'),
    ];
    $data['views']['helperbox_renderblock'] = [
        'title' => t('Render Block'),
        'help'  => t('Display the block content'),
        'field' => [
            'id' => 'helperbox_renderblock',
        ],
        'area' => [
            'id' => 'helperbox_renderblock',
        ],
        'group' => t('HelperBox'),
    ];
    $data['views']['helperbox_custom_text'] = [
        'title' => t('Full HTML Custom Text'),
        'help'  => t('Display full html content'),
        'field' => [
            'id' => 'helperbox_custom_text',
        ],
        'group' => t('HelperBox'),
    ];
    $data['views']['helperbox_add_media'] = [
        'title' => t('Add media'),
        'help'  => t('Add media'),
        'field' => [
            'id' => 'helperbox_add_media',
        ],
        'group' => t('HelperBox'),
    ];
    $data['views']['helperbox_add_cta'] = [
        'title' => t('Add CTA'),
        'help'  => t('Add CTA (Call To Action) button'),
        'field' => [
            'id' => 'helperbox_add_cta',
        ],
        'group' => t('HelperBox'),
    ];
}
