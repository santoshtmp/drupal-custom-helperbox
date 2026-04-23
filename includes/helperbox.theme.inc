<?php

/**
 * @file
 * Theme-related hooks for Helperbox module.
 */

/**
 * Implements hook_theme().
 */
function helperbox_theme() {
    $theme = [];

    $theme['helperbox_menu'] = [
        'variables' => [
            'menu_layout' => '',
            'menu_layout_location' => '',
            'menu_layout_subtype' => '',
            'mobile_menu_panel_title' => '',
            'menu_type' => '',
            'menu_title' => '',
            'menu_items' => [],
            'social_link_title' => '',
            'social_links' => '',
            'background_image' => '',
            'attributes' => [],
        ],
        'template' => 'helperbox-menu',
    ];
    $theme['helperbox_banner'] = [
        'variables' => [
            'section_title' => '',
            'section_sub_title' => '',
            'section_body' => '',
            'feature_video' => [],
            'feature_image' => [],
            'cta_links' => [],
            'video_links' => [],
            'block_layout' => '',
            'section_highlight' => '',
            'section_highlight_enable' => '',
            'background_image' => '',
            'secondary_body_enable' => '',
            'section_body_title_2' => '',
            'section_body_desc_2' => '',
            'lottie_file' => '',
        ],
        'template' => 'helperbox-banner',
    ];
    $theme['helperbox_content'] = [
        'variables' => [
            'section_title' => '',
            'section_sub_title' => '',
            'block_layout' => '',
            'content' => [],
            'show_edit_btn' => 0,
            'node_view_label' => '',
            'view_all_cta' => '',
            'other_meta_info' => '',
        ],
        'template' => 'helperbox-content',
    ];
    $theme['helperbox_contact'] = [
        'variables' => [
            'block_layout' => '',
            'section_title' => '',
            'section_body' => '',
            'email' => '',
            'phone_number' => '',
            'address' => '',
            'social_links' => [],
            'feature_media' => [],
            'media_type' => '',
            'selected_webform' => '',
            'find_us_title' => '',
            'contact_us_title' => '',
        ],
        'template' => 'helperbox-contact',
    ];
    $theme['helperbox_repeatedcontent'] = [
        'variables' => [
            'block_layout' => '',
            'section_title' => '',
            'section_sub_title' => '',
            'content_list' => [],
            'other_meta_info' => '',
        ],
        'template' => 'helperbox-repeatedcontent',
    ];
    $theme['helperbox_renderblock'] = [
        'variables' => [
            'content' => '',
            'block_type' => '',
            'block_plugin_id' => '',
            'view_id' => '',
            'display_id' => '',
            'adminlinks' => [],
            'attributes' => [],
        ],
        'template' => 'helperbox-renderblock',
    ];

    // CTA button field template for Views.
    $theme['helperbox_add_cta'] = [
        'variables' => [
            'cta_url' => '',
            'cta_label' => '',
            'cta_type' => 'primary',
            'cta_target' => '',
            'attributes' => [],
            'url_type' => 'internal',
            'is_external' => FALSE,
            'is_no_link' => FALSE,
        ],
        'template' => 'helperbox-add-cta',
    ];

    return $theme;
}

/**
 * Implements template suggestion for helperbox_menu
 */
function helperbox_theme_suggestions_helperbox_menu(array $variables) {
    $suggestions = [];
    $suggestions[] = 'helperbox_menu__layout_' . $variables['menu_layout'];
    $suggestions[] = 'helperbox_menu__menu_' . str_replace([' ', '-'], '_', $variables['menu_type']);
    $suggestions[] = 'helperbox_menu__layout_' . $variables['menu_layout'] . '__menu_' . str_replace([' ', '-'], '_', $variables['menu_type']);

    return $suggestions;
}

/**
 * Implements template suggestion for helperbox_banner
 */
function helperbox_theme_suggestions_helperbox_banner(array $variables) {

    $suggestions = [];
    if (isset($variables['block_layout'])) {
        $suggestions[] = 'helperbox_banner__layout_' . $variables['block_layout'];
    }
    return $suggestions;
}

/**
 * Implements template suggestion for helperbox_contact
 */
function helperbox_theme_suggestions_helperbox_contact(array $variables) {

    $suggestions = [];
    if (isset($variables['block_layout'])) {
        $suggestions[] = 'helperbox_contact__layout_' . $variables['block_layout'];
    }
    return $suggestions;
}

/**
 * Implements template suggestion for helperbox_repeatedcontent
 */
function helperbox_theme_suggestions_helperbox_repeatedcontent(array $variables) {

    $suggestions = [];
    if (isset($variables['block_layout'])) {
        $suggestions[] = 'helperbox_repeatedcontent__layout_' . $variables['block_layout'];
    }

    return $suggestions;
}

/**
 * Implements template suggestion for helperbox_content
 */
function helperbox_theme_suggestions_helperbox_content(array $variables) {

    $suggestions = [];
    if (isset($variables['block_layout'])) {
        $suggestions[] = 'helperbox_content__layout_' . $variables['block_layout'];
    }

    return $suggestions;
}

// /**
//  * Implements hook_theme_suggestions_page_alter().
//  */
// function helperbox_theme_suggestions_page_alter(&$suggestions, $variables, $hook) {
//     if (\Drupal::routeMatch()->getRouteName() == 'entity.node.canonical') {
//         $node = \Drupal::routeMatch()->getParameter('node');
//         $content_type = $node->bundle();
//         $suggestions[] = 'page__type__' . $content_type;
//     }
// }

// Implements hook_theme_suggestions_HOOK_alter() for form templates.
function helperbox_theme_suggestions_form_alter(array &$suggestions, array $variables) {
    $element = $variables['element'];
    $form_id = $element['#id'] ?? NULL;
    // Add suggestion for search block form
    if ($form_id) {
        // This will allow a template named form--search-block-form.html.twig
        $suggestions[] = 'form__' . str_replace('-', '_', $form_id);
    }
}

/**
 * Implements hook_theme_suggestions_views_view_field_alter().
 */
function helperbox_theme_suggestions_views_view_field_alter(array &$suggestions, array $variables) {

    $view = $variables['view'];
    $field = $variables['field'];
    $row = $variables['row'];
    //
    $view_id = $view->id();
    $display_id = $view->current_display;
    $field_name = $field->field;
    $entity = $row->_entity ?? NULL;

    if ($entity instanceof \Drupal\Core\Entity\ContentEntityInterface) {
        if (!$entity->hasField($field_name)) {
            return;
        }
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
        $entity_field = $entity->get($field_name);
        if ($entity_field instanceof \Drupal\Core\Field\EntityReferenceFieldItemListInterface) {

            $field_def = $entity_field->getFieldDefinition();
            $field_type = $field_def->getType();
            $target_type = $field_def->getSetting('target_type') ?? '';
            if ($target_type == 'paragraph' && $field_type == 'entity_reference_revisions') {
                $counter = 1;
                foreach ($entity_field->referencedEntities() as $paragraph) {
                    $field_name_id = $field->options['id'];
                    // Attach view context to the paragraph entity.
                    $paragraph->_helperbox_view_context = [
                        'counter' => $counter++,
                        'view_id' => $view_id,
                        'display_id' => $display_id,
                        'field_name' => $field_name,
                        'field_name_id' => $field_name_id,
                        'entity_type' => $entity->getEntityTypeId(),
                        'entity_id' => $entity->id(),
                        'entity_bundle' => $entity->bundle(),
                    ];
                }
            }
        }
    }
    // Add suggestions based on view, display, and field name.
    $suggestions[] = "views_view_field__" . $field_name;
    $suggestions[] = "views_view_field__" . $field_name . "__" . $view_id;
    $suggestions[] = "views_view_field__" . $field_name . "__" . $view_id . "__" . $display_id;
    // Remove duplicates
    $suggestions = array_unique($suggestions);
}

// Implements hook_theme_suggestions_paragraph_alter().
function helperbox_theme_suggestions_paragraph_alter(array &$suggestions, array $variables) {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['elements']['#paragraph'];

    if (!$paragraph instanceof \Drupal\paragraphs\Entity\Paragraph) {
        return;
    }

    // Get parent entity (node)
    $parent = $paragraph->getParentEntity();
    $current_path = \Drupal::service('path.current')->getPath();
    // // $alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
    // $isFrontPage = \Drupal::service('path.matcher')->isFrontPage() ? 'frontpage' : '';
    $current_path = str_replace('/', '', trim($current_path, '/'));

    // Add suggestions
    $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__path_' . $current_path;
    // check paragraph parent node
    if ($parent instanceof \Drupal\Core\Entity\EntityInterface && $parent->getEntityTypeId() === 'node') {
        $node = $parent;
        $node_id = $node->id();
        $field_name = $paragraph->get('parent_field_name')->value ?? NULL;

        // Add suggestions
        $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__' . $node->bundle();
        $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__' . $node->bundle() . '__' . $field_name;
        $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__node_' . $node_id;
        $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__node_' . $node_id . '__' . $field_name;
        $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__node_' . $node_id . '__path_' . $current_path;
    }

    // Suggest template based on view context (Views preview)
    if (isset($paragraph->_helperbox_view_context)) {
        $view_context = $paragraph->_helperbox_view_context;
        if (isset($view_context['view_id']) && isset($view_context['display_id'])) {
            $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__view_' . $view_context['view_id'] . '_' . $view_context['display_id'];
            $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__view_' . $view_context['view_id'] . '_' . $view_context['display_id'] . '__fieldid_' . $view_context['field_name_id'];
        }
    }
}

// Implements hook_theme_suggestions_field_alter().
function helperbox_theme_suggestions_field_alter(array &$suggestions, array $variables) {
    $field_name = $variables['element']['#field_name'] ?? '';
    if (!$field_name) {
        return;
    }
    // Get the entity that owns the field (paragraph or node)
    $entity = $variables['element']['#object'] ?? NULL;
    if (!$entity) {
        return;
    }
    //
    $base_field = 'field__' . $entity->getEntityTypeId() . '__' . str_replace('-', '_', $field_name) . '__' . $entity->bundle();
    if ($entity->getEntityTypeId() == 'paragraph') {
        $nid = $entity->get('parent_id')->value;
        // $node = \Drupal\node\Entity\Node::load($nid);
        $suggestions[] = $base_field . '__nid_' . $nid;
    }
    if ($entity->getEntityTypeId() == 'node') {
        $suggestions[] = $base_field . '__nid_' . $entity->id();
    }
}

// Implements hook_theme_suggestions_HOOK_alter().
function helperbox_theme_suggestions_colorbox_view_mode_formatter_alter(array &$suggestions, array $variables) {

    if (isset($variables['field_name'])) {
        $suggestions[] = 'colorbox_view_mode_formatter__field_' . $variables['field_name'];
    }

    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
        $suggestions[] = 'colorbox_view_mode_formatter__node_' . $node->bundle();
        $suggestions[] = 'colorbox_view_mode_formatter__node_' . $node->bundle() . '_' . $node->id();
    }
}
