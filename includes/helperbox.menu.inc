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
 * @param \Drupal\Core\Menu\MenuLinkTreeElement $element
 *   The full menu tree element.
 */
function helperbox_helperbox_menu_item_alter(&$menu_item, $link, $element) {
    try {
        $menu_item_type = ($link->getProvider()) ?: '';
        $is_external = $menu_item['is_external'] ?? false;
        if ($menu_item_type === 'menu_link_content') {
            $entity = \Drupal::service('menu_item_extras.menu_link_tree_handler')->getMenuLinkItemEntity($link);
            // field_description
            if ($entity->hasField('field_description') && !$entity->get('field_description')->isEmpty()) {
                if (!$entity->get('field_description')->isEmpty()) {
                    $menu_item['field_description'] = $entity->get('field_description')->value;
                }
            }
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
        }
    } catch (\Throwable $th) {
        //throw $th;
    }
}
