<?php

/**
 * @file
 * Preprocess hooks for Helperbox module.
 */

use Drupal\helperbox\Helper\UtilHelper;
use Drupal\helperbox\Helper\PreprocessNode;

// /**
//  * Implements hook_preprocess_HOOK() for page title templates.
//  */
// function helperbox_preprocess_page(&$variables): void {
// }

/**
 * Implements hook_preprocess_HOOK() for block.
 */
// function helperbox_preprocess_block(&$variables) {}

/**
 * Implement hook_preprocess_node()
 * preprocess node variables before they are passed to the node.html.twig template.
 */
function helperbox_preprocess_node(array &$variables) {
    try {
        if (isset($variables['node']) && $variables['node'] instanceof \Drupal\node\NodeInterface) {
            $node = $variables['node'];
            $node_type = $node->bundle();
            $method_name = $node_type . '_preprocess_node';
            if (method_exists('Drupal\helperbox\Helper\PreprocessNode', $method_name)) {
                PreprocessNode::$method_name($variables);
            }
        }
    } catch (\Throwable $th) {
        UtilHelper::helperbox_error_log($th);
    }
}

/**
 * Implements hook_preprocess_HOOK() for links templates.
 */
function helperbox_preprocess_links__language_block(array &$variables) {
    // Current language
    $variables['current_language'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
}

/**
 * Implements hook_preprocess_views_view_field().
 */
function helperbox_preprocess_views_view_field(&$variables) {
    $field = $variables['field'];
    $view = $variables['view'];
    $row = $variables['row'];
    $view_id = $view->id();
    $display_id = $view->current_display;

    // // Build method name: viewID_displayID_fieldName_field
    // $method_name = sprintf(
    //     '%s_%s_%s_field',
    //     $view->id(),
    //     $view->current_display,
    //     $field->field
    // );
    // // Only call if the static method exists in the helper class
    // if (method_exists('Drupal\helperbox\Helper\PreprocessViewsViewField', $method_name)) {
    //     PreprocessViewsViewField::$method_name($variables);
    // }
}

/**
 * Implements hook_preprocess_paragraph().
 */
function helperbox_preprocess_paragraph(array &$variables) {
    $paragraph = $variables['paragraph'];
    if (isset($paragraph->_helperbox_view_context)) {
        // Make the view context available in Twig
        $variables['helperbox_view_context'] = $paragraph->_helperbox_view_context;
    }
    if ($paragraph->bundle() == 'content_item' && $paragraph->hasField('field_file_upload')) {
        $field_file = $paragraph->get('field_file_upload');
        if (!$field_file->isEmpty()) {
            $item_id = $field_file->entity->id();
            if ($item_id) {
                $media = \Drupal\helperbox\Helper\MediaHelper::get_media_library_info($item_id);
                $variables['helperbox_paragraph']['field_file_upload'] = $media[0];
            }
        }
    }
}
