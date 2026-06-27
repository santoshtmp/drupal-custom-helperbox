<?php

/**
 * @file
 * Menu hooks for Helperbox module.
 */

use Drupal\helperbox\Helper\MediaHelper;

/**
 * Implements hook_helperbox_menu_item_alter().
 *
 * @param array $menu_item
 *   The menu item array being built (passed by reference).
 * @param \Drupal\Core\Menu\MenuLinkInterface $link
 *   The menu link plugin object.
 * @param string $menu_region_location
 *   The location of the menu region.
 */
function helperbox_helperbox_menu_item_alter(&$menu_item, $link, $menu_region_location) {
    try {
        $menu_item_type = ($link->getProvider()) ?: '';
        $is_external = $menu_item['is_external'] ?? false;
        if ($menu_item_type === 'menu_link_content') {
            $entity = \Drupal::service('menu_item_extras.menu_link_tree_handler')->getMenuLinkItemEntity($link);
            // field_icon
            if ($entity->hasField('field_icon') && !$entity->get('field_icon')->isEmpty()) {
                $field_icon_id = $entity->get('field_icon')->entity->id();
                if ($field_icon_id) {
                    $menu_item['field_icon'] = MediaHelper::get_media_library_info($field_icon_id);
                }
            }
            // field_override_external_link
            if ($entity->hasField('field_override_external_link') && !$entity->get('field_override_external_link')->isEmpty() && $is_external) {
                $override_link = $entity->get('field_override_external_link')->first()->getUrl();
                if ($override_link) {
                    $menu_item['url'] = $override_link;
                }
            }

            // field_description
            if ($entity->hasField('field_description') && !$entity->get('field_description')->isEmpty()) {
                $menu_item['field_description'] = $entity->get('field_description')->value;
            }

            // field_cta
            if ($entity->hasField('field_cta') && !$entity->get('field_cta')->isEmpty()) {
                $cta_field = $entity->get('field_cta')->first();

                // Link field returns uri and title
                $uri   = $cta_field->uri;
                $label = $cta_field->title;

                // Convert uri to Url object
                $url = \Drupal\Core\Url::fromUri($uri);

                $menu_item['field_cta'] = [
                    '#theme'       => 'helperbox_add_cta',
                    '#cta_url'     => $url->toString(),
                    '#cta_label'   => !empty($label) ? $label : $url->toString(),
                    '#cta_type'    => 'link',
                    '#cta_target'  => NULL,
                    '#is_external' => $url->isExternal(),
                    '#is_no_link'  => FALSE,
                    '#attributes'  => [],
                ];
            }

            // field_menu_location
            if ($entity->hasField('field_menu_location') && !$entity->get('field_menu_location')->isEmpty()) {
                $field_menu_locations = array_column(
                    $entity->get('field_menu_location')->getValue(),
                    'value'
                );
                if (!empty($field_menu_locations) && is_array($field_menu_locations)) {
                    $menu_item['field_menu_location'] = $field_menu_locations;
                }
            }
        }
    } catch (\Throwable $th) {
        //throw $th;
    }
}


/**
 * Implements hook_helperbox_menu_other_variables_alter().
 *
 * @param array $other_variables
 *   An array of other variables to be passed to the template (passed by reference).
 * @param array $config
 *   The block configuration array.
 */
function helperbox_helperbox_menu_other_variables_alter(&$other_variables, $config) {
    try {
        // Implementation for altering other variables
    } catch (\Throwable $th) {
        //throw $th;
    }
}


/**
 * Implements hook_form_alter().
 *
 * @param array $form
 *   The form array (passed by reference).
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state object.
 * @param string $form_id
 *   The ID of the form being altered.
 */
function add_menu_location_at_menu_edit_form(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {

    if ($form_id !== 'menu_edit_form') {
        return;
    }

    if (!isset($form['links']['links'])) {
        return;
    }

    // Insert "Menu Location" header as the second column.
    if (isset($form['links']['links']['#header']) && is_array($form['links']['links']['#header'])) {
        $header = $form['links']['links']['#header'];
        array_splice($header, 1, 0, [t('Menu Location')]);
        $form['links']['links']['#header'] = $header;
    }

    /**
     * -------------------------
     * Loop rows
     * -------------------------
     */
    foreach ($form['links']['links'] as $key => &$row) {

        // Skip table metadata.
        if (!is_array($row) || !isset($row['#item'])) {
            continue;
        }

        $menu_location = '';

        try {
            $link = $row['#item']->link;
            $menu_item_type = $link->getProvider() ?: '';

            if ($menu_item_type === 'menu_link_content') {
                $entity = \Drupal::service('menu_item_extras.menu_link_tree_handler')
                    ->getMenuLinkItemEntity($link);

                if (
                    $entity &&
                    $entity->hasField('field_menu_location') &&
                    !$entity->get('field_menu_location')->isEmpty()
                ) {
                    $field = $entity->get('field_menu_location');

                    // $field_menu_locations = array_column(
                    //     $field->getValue(),
                    //     'value'
                    // );

                    // if (!empty($field_menu_locations) && is_array($field_menu_locations)) {
                    //     $menu_location = '<ul><li>' .
                    //         implode('</li><li>', array_map('htmlspecialchars', $field_menu_locations)) .
                    //         '</li></ul>';
                    // }

                    // Get allowed values (value => label)
                    $allowed_values = $field->getFieldDefinition()
                        ->getSetting('allowed_values');
                    $stored_values = $field->getValue();
                    $labels = [];

                    foreach ($stored_values as $item) {
                        $value = $item['value'] ?? NULL;

                        if ($value !== NULL && isset($allowed_values[$value])) {
                            $labels[] = $allowed_values[$value];
                        }
                    }

                    if (!empty($labels)) {
                        $menu_location = '<ul><li>' .
                            implode('</li><li>', array_map('htmlspecialchars', $labels)) .
                            '</li></ul>';
                    }
                }
            }
        } catch (\Exception $e) {
            $menu_location = '';
        }

        $location_column = [
            '#type' => 'item',
            '#markup' => $menu_location,
        ];

        // Rebuild row and insert Menu Location after title column.
        $new_row = [];

        foreach ($row as $name => $element) {
            $new_row[$name] = $element;

            if ($name === 'title') {
                $new_row['menu_location'] = $location_column;
            }
        }

        $row = $new_row;
    }
}
