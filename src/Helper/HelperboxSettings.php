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
     * @var array<string, array<string, array{
     *     field_access_check?: array<string, bool>
     * }>>
     * 
     */
    public static $allfieldrules = [
        // 'node' => [
        //     'understanding_fimi' => [
        //         'field_access_check' => [
        //             'field_related_countries' => false,
        //         ]
        //     ],
        // ],
        // 'paragraph' => [
        //     'content_item' => [
        //         'field_access_check' => [
        //             'field_list_items' => false,
        //             'field_highlight_text' => false,
        //             'field_file_upload' => false,
        //         ]
        //     ],
        //     'list_item' => [
        //         'field_access_check' => [
        //             'field_description_2' => false,
        //             'field_featured_image' => false,
        //             'field_link' => false,
        //         ]
        //     ],
        // ],

    ];

    /**
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
     * @var array<string, array<int, array<string, mixed>>>
     * 
     */
    public static $nodefieldrules = [
        // 'understanding_fimi' => [
        //     16 => [
        //         'group_fimi_vs_disinformation',
        //         'referenceField' => [
        //             'field_content_section' => [
        //                 'field_list_items' => false,
        //             ]
        //         ]
        //     ]
        // ],
        // 'page' => [
        //     '15' => [
        //         'referenceField' => [
        //             'field_content_section' => [
        //                 'field_highlight_text' => true,
        //             ]
        //         ]
        //     ]

        // Add more content types here…
    ];

    /**
     * 
     * Example:
     * [
     *  'form_id_...'=>[
     *      'field_...'=>true|false
     *  ]
     * ]
     */
    public static $formIdFieldsrules = [
        'search_form' => [
            'advanced' => false,
        ]
    ];

    /**
     * Maximum allowed nodes per content type.
     *
     * Example:
     * [
     *      'content_type_...' => Number,
     * ]
     * @var array<string, int>
     */
    public static $maxContentNodes = [
        'understanding_fimi' => 3,
        // 'article' => 4
    ];


    // END
}
