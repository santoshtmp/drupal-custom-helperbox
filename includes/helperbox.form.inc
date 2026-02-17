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
 */
function helperbox_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
    // Call helper to apply more complex field rules.
    FormField::applyFormFieldCondition($form, $form_state, $form_id);
}

