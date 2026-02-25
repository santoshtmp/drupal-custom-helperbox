<?php

namespace Drupal\helperbox\Helper;

/**
 * Helperbox Config Settings class
 *
 * @package Drupal\helperbox\Helper
 */
class HelperboxSettings {

    /**
     *
     */
    public static function get_config($field_name = '') {
        try {
            $config = \Drupal::config('helperbox.settings');
            if (!$field_name) {
                return $config;
            }
            return $config->get($field_name);
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Get field rules from configuration.
     * Rules for field access based on entity type and bundle.
     *
     * Example:
     * [
     *      'entity_type_...' => [
     *          'bundle_...' => [
     *              'field_access_check' => [
     *                  'field_...' => true|false,
     *              ],
     *          ],
     *      ]
     *  ]
     *
     * @return array
     *   The field rules configuration.
     */
    public static function getFieldRulesAll() {
        $config_rules = self::get_config('field_rules_all');
        if (!empty($config_rules)) {
            return $config_rules;
        }
        return [];
        // $allfieldrules = [
        //     'node' => [
        //         'article' => [
        //             'field_access_check' => [
        //                 'field_related_countries' => false,
        //             ]
        //         ],
        //     ],
        //     'paragraph' => [
        //         'content_item' => [
        //             'field_access_check' => [
        //                 'field_list_items' => false,
        //                 'field_highlight_text' => false,
        //                 'field_file_upload' => false,
        //             ]
        //         ],
        //         'list_item' => [
        //             'field_access_check' => [
        //                 'field_description_2' => false,
        //                 'field_featured_image' => false,
        //                 'field_link' => false,
        //             ]
        //         ],
        //     ],
        // ];
    }

    /**
     * Get node field rules from configuration.
     * Field rules for specific content type and node ID.
     *
     * Example:
     * [
     *      'content_type_...' => [
     *          'node_id_...' => [
     *              'field_...',
     *              'group_...',
     *              [
     *                  'field_...' => true|false
     *              ],
     *              'referenceField' => [
     *                  'field_...' => true|false,
     *                  'field_...' => [
     *                      'field_...' => true|false,
     *                      'referenceField' => [
     *                          'field_...'=> true|false
     *                      ],
     *                  ],
     *              ],
     *          ],
     *      ]
     * ]
     *
     * @return array
     *   The node field rules configuration.
     *
     */
    public static function getFieldRulesNode() {
        $config_rules = self::get_config('field_rules_node');
        if (!empty($config_rules)) {
            return $config_rules;
        }
        return [];
        // $nodefieldrules = [
        //     'article' => [
        //         16 => [
        //             'group_general_section',
        //             [
        //                 'field_cta_action' => true
        //             ],
        //             'referenceField' => [
        //                 'field_content_section' => [
        //                     'field_list_items' => false,
        //                 ]
        //             ]
        //         ]
        //     ],
        //     'page' => [
        //         15 => [
        //             'referenceField' => [
        //                 'field_content_section' => [
        //                     'field_highlight_text' => true,
        //                 ]
        //             ]
        //         ],
        //     ]
        // ];
    }

    /**
     * Get form field rules from configuration.
     * 
     * Example:
     * [
     *  'form_id_...'=>[
     *      'field_...'=>true|false
     *  ]
     * ]
     *
     * @return array
     *   The form field rules configuration.
     */
    public static function getFieldRulesForm() {
        $config_rules = self::get_config('field_rules_form');
        if (!empty($config_rules)) {
            return $config_rules;
        }
        return [];
        // $formIdFieldsrules = [
        //     'search_form' => [
        //         'advanced' => false,
        //     ]
        // ];
    }

    /**
     * Get maximum content nodes from configuration.
     * Maximum allowed nodes per content type.
     *
     * Example:
     * [
     *      'content_type_...' => Number,
     * ]
     *
     * @return array
     *   The maximum content nodes configuration.
     */
    public static function getFieldRulesMaxContent() {
        $config_rules = self::get_config('field_rules_max_content');
        if (!empty($config_rules)) {
            return $config_rules;
        }
        return [];
        // $maxContentNodes = [
        //     'article' => 4
        // ];
    }

    /**
     * Check if unique node/item per content bundle is enabled.
     *
     * @return bool
     *   TRUE if enabled, FALSE otherwise.
     */
    public static function isUniqueNodePerBundleEnabled() {
        return (bool) self::get_config('enable_unique_node_per_bundle');
    }

    // END
}
