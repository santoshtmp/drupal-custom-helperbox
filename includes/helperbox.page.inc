<?php

/**
 * @file
 * Page attachment hooks for Helperbox module.
 */

use Drupal\helperbox\Helper\FormField;

/**
 * Implements hook_page_attachments_alter().
 */
function helperbox_page_attachments_alter(array &$attachments) {

    // Attach global library for Helperbox
    $attachments['#attached']['library'][] = 'helperbox/helperbox_styles';

    // Only run on admin routes
    $admin_context = \Drupal::service('router.admin_context');
    if (!$admin_context->isAdminRoute()) {
        return;
    }

    // Attach the library
    $attachments['#attached']['library'][] = 'helperbox/helperbox_admin_styles';

    // Get current route and parameters
    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();
    $parameters = $route_match->getParameters();
    $request = \Drupal::request();
    $path = $request->getPathInfo();

    // Remove language prefix if present
    $language_prefix = '/' . \Drupal::languageManager()->getCurrentLanguage()->getId();
    if (strpos($path, $language_prefix) === 0) {
        $path = substr($path, strlen($language_prefix));
    }

    // Content type to check
    $contentType = 'understanding_fimi';

    // Check if maximum nodes for this content type is reached
    if (FormField::maxNodeValidate($contentType)) {

        // Pass info to JS (for hiding Add Node links)
        $attachments['#attached']['drupalSettings']['maxNodeValidate'][$contentType] = true;

        // Get human-readable content type label
        $type_label = \Drupal\node\Entity\NodeType::load($contentType)->label();

        // Show status message only on:
        // 1. Node add page for this content type
        // 2. Admin content page for this content type
        if ($route_name === 'node.add' && $parameters->get('node_type')->id() === $contentType) {
            \Drupal::messenger()->addStatus(
                t(
                    'You cannot create a new "@value" node — the maximum number has been reached.',
                    ['@value' => $type_label]
                )
            );
        }
    }
}
