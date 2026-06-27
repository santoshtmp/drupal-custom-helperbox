<?php

/**
 * @file
 * Form hooks for Helperbox module.
 */

use Drupal\helperbox\Helper\FormField;

// /**
//  * Implements hook_webform_submission_insert().
//  */
// function helperbox_webform_submission_insert(\Drupal\webform\Entity\WebformSubmission $webform_submission) {
// }

/**
 * Implements hook_form_alter().
 * 
 * @param array $form
 *   An associative array containing the structure of the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   An object containing the current state of the form.
 * @param string $form_id
 *   The unique ID of the form.
 * 
 * This hook is used to alter the menu edit form to add a new column that displays the URL of each menu link. It checks if the form being altered is the 'menu_edit_form' and if the 'links' table exists in the form. If so, it adds a new header for the URL column and iterates through each menu link row to extract and display the URL in a new cell. The URL is displayed as a small, escaped string to ensure it is safe for output. Additionally, it calls a helper function to apply more complex field rules based on the form ID.
 * 
 */
function helperbox_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {

    // Call the function to add the menu location column to the menu edit form.
    add_menu_location_at_menu_edit_form($form, $form_state, $form_id);

    // Call helper to apply more complex field rules.
    FormField::get_instance()->applyFormFieldCondition($form, $form_state, $form_id);
}
