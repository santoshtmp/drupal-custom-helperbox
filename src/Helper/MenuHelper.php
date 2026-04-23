<?php

/**
 * @file
 * Contains \Drupal\helperbox\Helper\MenuHelper.
 *
 * @see https://www.drupal.org/docs/creating-modules/creating-custom-blocks/create-a-custom-block-plugin
 * @see https://lembergsolutions.com/blog/get-fieldable-drupal-menu-menu-item-extras-overview
 * @see core\modules\navigation\src\Plugin\Block\NavigationMenuBlock.php
 */

namespace Drupal\helperbox\Helper;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Template\Attribute;

/**
 * Provides helper methods for working with Drupal menus.
 *
 * This class contains static utility methods for retrieving menu information,
 * loading menu trees, and transforming them into custom array structures
 * suitable for theming and rendering.
 *
 * @package Drupal\helperbox\Helper
 *
 * @see \Drupal\Core\Menu\MenuTreeParameters
 * @see \Drupal\Core\Menu\MenuLinkTreeElement
 */
class MenuHelper {

    /**
     * Retrieves a list of all available menus.
     *
     * @return array
     *   An associative array of menus, keyed by menu machine name with menu
     *   labels as values.
     *
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public static function get_all_menus(): array {
        $menus = \Drupal::entityTypeManager()->getStorage('menu')->loadMultiple();
        $menu_list = [];
        foreach ($menus as $menu) {
            $menu_list[$menu->id()] = $menu->label();
        }
        return $menu_list;
    }

    /**
     * Retrieves the title of a menu by its machine name.
     *
     * @param string $menu_name
     *   The machine name of the menu to load. Defaults to 'main'.
     *
     * @return string|null
     *   The label of the menu if found, or NULL if the menu does not exist.
     *
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public static function get_menu_title(string $menu_name = 'main'): ?string {
        $menu_storage = \Drupal::entityTypeManager()->getStorage('menu');
        $menu = $menu_storage->load($menu_name);
        return $menu ? $menu->label() : NULL;
    }

    /**
     * Retrieves menu items for a given menu with configurable depth.
     *
     * Loads the menu tree, applies standard manipulators (access checking,
     * sorting), and returns a processed array of menu items.
     *
     * @param string $menu_name
     *   The machine name of the menu to load. Defaults to 'main'.
     * @param int $max_menu_levels
     *   The maximum number of menu levels to include. Set to 0 for unlimited.
     *   Defaults to 1.
     *
     * @return array
     *   A nested array of menu items suitable for rendering.
     *
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     *
     * @see https://www.drupal.org/project/menu_item_extras
     * @see \Drupal\Core\Menu\MenuTreeParameters
     * @see core\modules\navigation\src\Plugin\Block\NavigationMenuBlock.php
     */
    public static function get_menu_items(string $menu_name = 'main', int $max_menu_levels = 1): array {
        $parameters = new MenuTreeParameters();
        $parameters->onlyEnabledLinks();
        $menu_tree = \Drupal::menuTree();
        $tree = $menu_tree->load($menu_name, $parameters);

        // Process tree (apply permissions, sorting).
        $manipulators = [
            ['callable' => 'menu.default_tree_manipulators:checkAccess'],
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ];
        $tree = $menu_tree->transform($tree, $manipulators);

        // Convert tree to custom array.
        return self::build_menu_array($tree, $max_menu_levels);
    }

    /**
     * Recursively builds a custom array from a menu tree.
     *
     * Transforms menu tree elements into a structured array with metadata
     * such as URLs, active states, and nesting information. Allows modules
     * and themes to alter individual menu items via hooks.
     *
     * @param array $tree
     *   An array of MenuLinkTreeElement objects representing the menu tree.
     * @param int $max_menu_levels
     *   The maximum number of menu levels to include. Set to 0 for unlimited.
     * @param int $menu_level
     *   The current depth level being processed. Defaults to 1.
     *
     * @return array
     *   A nested array of menu item data with the following structure:
     *   - id: The plugin ID of the menu link.
     *   - title: The display title of the menu link.
     *   - url: The URL object for the link.
     *   - is_external: Boolean indicating if the link is external.
     *   - weight: The weight of the menu link.
     *   - menu_level: The depth level of the menu item.
     *   - menu_item_type: The provider of the menu link.
     *   - is_active: Boolean indicating if the link matches the current path.
     *   - in_active_trail: Boolean indicating if the link is in the active trail.
     *   - attributes: An Attribute object for rendering.
     *   - below: (optional) Nested array of child menu items.
     *
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     *
     * @see \Drupal\Core\Menu\MenuLinkTreeElement
     * @see hook_helperbox_menu_item_alter()
     */
    public static function build_menu_array(array $tree, int $max_menu_levels, int $menu_level = 1): array {
        $menu_items = [];
        $current_path = \Drupal::service('path.current')->getPath();

        foreach ($tree as $element) {
            if ($max_menu_levels !== 0 && $menu_level > $max_menu_levels) {
                break;
            }

            /** @var \Drupal\Core\Menu\MenuLinkTreeElement $element */
            $link = $element->link;
            $subtree = $element->subtree;

            // Get the menu item's URL.
            $url_object = $link->getUrlObject();
            $is_external = $url_object->isExternal();

            // Determine if this link matches the current path.
            $is_active = ($url_object->toString() === $current_path);

            // Get the menu item provider.
            $menu_item_type = $link->getProvider() ?: '';

            // Build menu item array.
            $menu_item = [
                'id' => $link->getPluginId(),
                'title' => $link->getTitle(),
                'url' => $url_object,
                'is_external' => $is_external,
                'weight' => $link->getWeight(),
                'menu_level' => $menu_level,
                'menu_item_type' => $menu_item_type,
                'is_active' => $is_active,
                'in_active_trail' => $element->inActiveTrail,
                'attributes' => new Attribute(),
            ];

            // Allow modules/themes to alter the menu item. using hook hook_helperbox_menu_item_alter().
            \Drupal::moduleHandler()->alter('helperbox_menu_item', $menu_item, $link, $element);
            \Drupal::theme()->alter('helperbox_menu_item', $menu_item, $link, $element);

            // Recursively process child menu items.
            if (!empty($subtree)) {
                $menu_item['below'] = self::build_menu_array($subtree, $max_menu_levels, $menu_level + 1);
            }

            $menu_items[] = $menu_item;
        }

        return $menu_items;
    }

}
