<?php

/**
 * @file
 * Views hooks for Helperbox module.
 */

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;

/**
 * Implements hook_views_query_alter().
 *
 * Filter Config Pages views to the current language.
 */
function helperbox_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
    try {
        // check if the view is for Config Pages.
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

        // Build safe LIKE patterns.
        $like_json = '%"language":"' . $current_lang . '"%';

        $length = strlen($current_lang);
        $like_php = '%s:8:"language";s:' . $length . ':"' . $current_lang . '"%';

        $expression = "(context LIKE :lang_json OR context LIKE :lang_php)";

        // Add to the default WHERE group (0).
        $query->addWhereExpression(0, $expression, [
            ':lang_json' => $like_json,
            ':lang_php'  => $like_php,
        ]);
    } catch (\Throwable $th) {
        //throw $th;
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
}
