/**
 * @file
 * Conditional field visibility behaviors for node edit forms.
 *
 */
(function ($, Drupal, drupalSettings, once) {
  'use strict';

  // ---------------------------------------------------------------------------
  // Utility helpers
  // ---------------------------------------------------------------------------

  /**
   * Converts a field group machine name to its corresponding DOM element ID.
   *
   * Drupal renders horizontal tab panes with IDs derived from the group's
   * machine name: underscores become hyphens and the string is prefixed with
   * "edit-".
   *
   * @example
   *   groupToElementId('group_course_objectives')
   *   // → 'edit-group-course-objectives'
   *
   * @param {string} group
   *   Field group machine name (e.g. 'group_course_objectives').
   * @return {string}
   *   DOM element ID without the leading '#'.
   */
  function groupToElementId(group) {
    return 'edit-' + group.replace(/_/g, '-');
  }

  /**
   * Converts a field machine name to its wrapper element DOM ID.
   *
   * Drupal wraps each field widget in a div whose ID follows the pattern
   * "edit-{field-name}-wrapper", with underscores replaced by hyphens.
   *
   * @example
   *   fieldToElementId('field_venue')
   *   // → 'edit-field-venue-wrapper'
   *
   * @param {string} field
   *   Field machine name (e.g. 'field_venue').
   * @return {string}
   *   Wrapper DOM element ID without the leading '#'.
   */
  function fieldToElementId(field) {
    return 'edit-' + field.replace(/_/g, '-') + '-wrapper';
  }

  // ---------------------------------------------------------------------------
  // Group / field show-hide helpers
  // ---------------------------------------------------------------------------

  /**
   * Hides field group panes and their corresponding horizontal tab links.
   *
   * Each horizontal tab group has two DOM representations: the content pane
   * (e.g. #edit-group-gallery) and the tab anchor whose href points to that
   * pane. Both must be hidden so the tab does not remain visible in the tab
   * bar after its content is removed from view.
   *
   * @param {string[]} groups
   *   Field group machine names to hide.
   */
  function hideGroupDetails(groups) {
    groups.forEach(function (group) {
      const id = '#' + groupToElementId(group);
      $(id).hide();
      $('a[href="' + id + '"]').hide();
    });
  }

  /**
   * Shows field group panes and their corresponding horizontal tab links.
   *
   * Reverses the effect of {@link hideGroupDetails} for the given groups.
   *
   * @param {string[]} groups
   *   Field group machine names to show.
   */
  function showGroupDetails(groups) {
    groups.forEach(function (group) {
      const id = '#' + groupToElementId(group);
      $(id).show();
      $('a[href="' + id + '"]').show();
    });
  }

  /**
   * Hides individual field wrapper elements.
   *
   * Targets the outer wrapper div Drupal generates for each field
   * (e.g. #edit-field-venue-wrapper), not the inner widget element.
   *
   * @param {string[]} fields
   *   Field machine names whose wrappers should be hidden.
   */
  function hideFieldWrapper(fields) {
    fields.forEach(function (field) {
      $('#' + fieldToElementId(field)).hide();
    });
  }

  /**
   * Shows individual field wrapper elements.
   *
   * Reverses the effect of {@link hideFieldWrapper} for the given fields.
   *
   * @param {string[]} fields
   *   Field machine names whose wrappers should be shown.
   */
  function showFieldWrapper(fields) {
    fields.forEach(function (field) {
      $('#' + fieldToElementId(field)).show();
    });
  }

  // ---------------------------------------------------------------------------
  // Training structure UI logic
  // ---------------------------------------------------------------------------

  /**
   * Applies form field visibility based on the selected training structure.
   *
   * Reads the current value of the training structure select element and
   * toggles two mutually exclusive sets of field groups and fields:
   *
   *  - "instance": show instance-specific groups/fields; hide main ones.
   *  - any other value: show main groups/fields; hide instance-specific ones.
   *
   * After updating visibility, programmatically clicks the "Overview" tab to
   * prevent the user from remaining on a tab that has just been hidden.
   *
   * @param {HTMLElement} selectTrainingStructureElement
   *   The <select> element for field_training_structure.
   * @param {string[]} main_training_groups
   *   Field group machine names shown only for main (non-instance) trainings.
   * @param {string[]} main_training_fields
   *   Field machine names shown only for main (non-instance) trainings.
   * @param {string[]} instance_training_groups
   *   Field group machine names shown only for instance trainings.
   * @param {string[]} instance_training_fields
   *   Field machine names shown only for instance trainings.
   */
  function updateTrainingEditFormUIByTrainingstructure(
    selectTrainingStructureElement,
    main_training_groups,
    main_training_fields,
    instance_training_groups,
    instance_training_fields
  ) {
    const trainingStructureVal = $(selectTrainingStructureElement).val();

    if (trainingStructureVal === 'instance') {
      hideGroupDetails(main_training_groups);
      hideFieldWrapper(main_training_fields);
      showGroupDetails(instance_training_groups);
      showFieldWrapper(instance_training_fields);
    }
    else {
      hideGroupDetails(instance_training_groups);
      hideFieldWrapper(instance_training_fields);
      showGroupDetails(main_training_groups);
      showFieldWrapper(main_training_fields);
    }

    // Return focus to the Overview tab so the user is never left viewing
    // a tab that has just been hidden.
    $('a[href="#edit-group-overview"]')[0]?.click();
  }

  // ---------------------------------------------------------------------------
  // Drupal behaviors
  // ---------------------------------------------------------------------------

  /**
   * Placeholder behavior reserved for future conditional field logic.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.nodeFormConditionalFields = {
    attach: function (context) {
      // Reserved for additional conditional field behaviors.
    },
  };

  /**
   * Toggles field groups and fields based on training structure, and controls
   * list item subform field visibility based on the selected list item type.
   *
   * Training structure (field_training_structure):
   *   - "instance" → shows instance-specific groups/fields, hides main ones.
   *   - any other value → shows main groups/fields, hides instance-specific ones.
   *
   *   Main training groups/fields:
   *     groups: group_competency_framework, group_course_objectives,
   *             group_training_methods, group_course_modules_and_classro, group_kyc
   *     fields: field_short_name, field_featured_image, field_contact_person
   *
   *   Instance training groups/fields:
   *     groups: group_gallery, group_participants
   *     fields: field_parent_training, field_venue, field_date_range,
   *             field_duration, field_course_coodinator
   *
   * List item type (field_list_item_type within course module subforms):
   *   - "points"    → title only.
   *   - "accordion" → title + description.
   *   - default     → title + subtitle + description.
   *
   * Uses once() throughout to ensure listeners and initial UI state are only
   * applied once per element, even when Drupal.attachBehaviors() is called
   * multiple times (e.g. after AJAX rebuilds).
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.trainingStructureChange = {
    attach: function (context) {

      // -----------------------------------------------------------------------
      // Training structure: toggle main vs. instance field groups and fields.
      // -----------------------------------------------------------------------

      once('training-structure-Change', 'select[name="field_training_structure"]', context)
        .forEach(function (selectTrainingStructureElement) {

          // Field groups visible only for instance trainings.
          const instance_training_groups = [
            'group_gallery',
            'group_participants',
          ];

          // Fields visible only for instance trainings.
          const instance_training_fields = [
            'field_parent_training',
            'field_venue',
            'field_date_range',
            'field_duration',
            'field_course_coodinator',
          ];

          // Field groups visible only for main (non-instance) trainings.
          const main_training_groups = [
            'group_competency_framework',
            'group_course_objectives',
            'group_training_methods',
            'group_course_modules_and_classro',
            'group_kyc',
          ];

          // Fields visible only for main (non-instance) trainings.
          const main_training_fields = [
            'field_short_name',
            'field_featured_image',
            'field_contact_person',
          ];

          // Apply the correct visibility state on page load before any user
          // interaction, based on the field's current persisted value.
          updateTrainingEditFormUIByTrainingstructure(
            selectTrainingStructureElement,
            main_training_groups,
            main_training_fields,
            instance_training_groups,
            instance_training_fields
          );

          // Re-evaluate and reapply visibility rules on every subsequent change.
          $(selectTrainingStructureElement).on('change', function () {
            updateTrainingEditFormUIByTrainingstructure(
              this,
              main_training_groups,
              main_training_fields,
              instance_training_groups,
              instance_training_fields
            );
          });

        });

      // -----------------------------------------------------------------------
      // List item type: show/hide subform fields per list item type selection.
      // -----------------------------------------------------------------------

      // Matches the subform wrapper for any list item within a course module.
      const field_list_items_selector = 'div[data-drupal-selector^="edit-field-course-modules-learnings-"][data-drupal-selector*="-subform-field-list-items-"][data-drupal-selector$="-subform"]';

      // Matches the list item type select within those subforms.
      const field_list_item_type_selector = 'select[data-drupal-selector^="edit-field-course-modules-learnings-"][data-drupal-selector$="-subform-field-list-item-type"]';

      /**
       * Shows or hides list item subform fields based on the selected type.
       *
       *  - "points":    show title only; hide subtitle and description.
       *  - "accordion": show title and description; hide subtitle.
       *  - default:     show title, subtitle, and description.
       *
       * @param {string} listItemTypeVal
       *   The selected list item type value.
       * @param {jQuery} $itemWrapper
       *   The subform wrapper element containing the fields to toggle.
       */
      function list_items_control(listItemTypeVal, $itemWrapper) {
        const $fieldTitle = $itemWrapper.find('div[data-drupal-selector$="-subform-field-title-wrapper"]');
        const $fieldSubTitle = $itemWrapper.find('div[data-drupal-selector$="-subform-field-sub-title-wrapper"]');
        const $fieldDescription = $itemWrapper.find('div[data-drupal-selector$="-subform-field-description-wrapper"]');

        if (listItemTypeVal === 'points') {
          $fieldTitle.show();
          $fieldSubTitle.hide();
          $fieldDescription.hide();
        }
        else if (listItemTypeVal === 'accordion') {
          $fieldTitle.show();
          $fieldSubTitle.hide();
          $fieldDescription.show();
        }
        else {
          $fieldTitle.show();
          $fieldSubTitle.show();
          $fieldDescription.show();
        }
      }

      // Apply initial field visibility for each list item subform on page load.
      once('field-list-items-condition', field_list_items_selector, context)
        .forEach(function (element) {
          const $wrapper = $(element);
          const $listItemType = $(field_list_item_type_selector);
          if ($listItemType.length) {
            list_items_control($listItemType.val(), $wrapper);
          }
        });

      // When the list item type changes, update visibility across all subforms.
      once('field-list-items-type-condition', field_list_item_type_selector, context)
        .forEach(function (element) {
          $(element).on('change', function () {
            const listItemTypeVal = $(this).val();
            document.querySelectorAll(field_list_items_selector).forEach(function (itemElement) {
              list_items_control(listItemTypeVal, $(itemElement));
            });
          });
        });

    },
  };

  // ---------------------------------------------------------------------------
  // Training participant group filter behavior
  // ---------------------------------------------------------------------------

  /**
   * Replaces the participant group filter text input with dynamic radio buttons
   * and filters participant rows based on the selected group.
   *
   * On attach, the raw text input for field_participant_group_filter is hidden
   * and replaced with a set of radio buttons. An "All" option is added by
   * default; additional options are generated from the group values already
   * selected in participant row select fields.
   *
   * When a radio button is selected:
   *  - The corresponding filter value is written back to the hidden text field.
   *  - Each participant row is shown or hidden based on whether its group
   *    matches the active filter (unassigned rows with '_none' always show).
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.trainingParticipantGroupFilter = {
    attach: function (context) {

      /** @type {string[]} Group values already represented as radio options, used to avoid duplicates. */
      let selectedGroupValues = [];

      /**
       * Appends a radio button option to the participant group filter list.
       *
       * Creates the filter option list wrapper if it does not yet exist.
       * Silently skips insertion if an option with the same value is already
       * present (checked by ID).
       *
       * @param {string} value
       *   The option value (e.g. a taxonomy term ID, or 'all').
       * @param {string} label
       *   Human-readable label rendered next to the radio button.
       * @param {boolean} [active=false]
       *   Whether the radio button should be rendered in the checked state.
       */
      function addParticipantGroupFilterOption(value, label, active = false) {
        if ($('#participant-group-filter-' + value).length) {
          return;
        }

        const checked = active ? 'checked' : '';

        const option = `
          <div class="form-type--radio form-type--boolean participant-group-filter-option">
            <input
              type="radio"
              name="training_participant_group_filter"
              value="${value}"
              class="form-radio form-boolean form-boolean--type-radio"
              id="participant-group-filter-${value}"
              ${checked}
            >
            <label
              for="participant-group-filter-${value}"
              class="form-item__label option"
            >
              ${label}
            </label>
          </div>
        `;

        const wrapperId = 'training-participant-group-filter-option-list';
        let $filterList = $('#' + wrapperId);

        // Create the radio group wrapper on first use.
        if (!$filterList.length) {
          $('#edit-field-participant-group-filter-wrapper').append(`
            <div
              id="${wrapperId}"
              class="form-radios form-boolean-group participant-group-filter-option-list"
            ></div>
          `);
          $filterList = $('#' + wrapperId);
        }

        $filterList.append(option);
      }

      /**
       * Shows or hides a single participant row based on the active filter.
       *
       * A row is visible when any of the following is true:
       *  - The active filter is 'all' or empty (no filter applied).
       *  - The row's group value matches the active filter value.
       *  - The row's group value is empty or '_none' (unassigned rows always show).
       *
       * @param {jQuery} $element
       *   The group <select> element within the participant row.
       * @param {string} currentFilterValue
       *   The currently active filter value.
       */
      function participationListHideShow($element, currentFilterValue) {
        const value = $element.val();
        const $row = $element.closest('tr.paragraph-type--participant');

        const shouldShow =
          currentFilterValue === value ||
          currentFilterValue === 'all' ||
          currentFilterValue === '' ||
          value === '' ||
          value === '_none';

        $row.toggle(shouldShow);
      }

      // Initialize the filter wrapper once: hide the raw text input and insert
      // the default "All" radio option.
      once('participant-group-filter-option', '#edit-field-participant-group-filter-wrapper', context)
        .forEach(function (participantGroupFilter) {
          $(participantGroupFilter).find('input[type="text"]').hide();
          addParticipantGroupFilterOption('all', 'All', true);
        });

      // Process each participant group <select> once:
      //  - Register its current value as a radio filter option.
      //  - Apply the currently active filter to show/hide its row on load.
      //  - Add new radio options dynamically as the user changes group values.
      once('participant-group-option', 'select[data-drupal-selector$="subform-field-participant-group"]', context)
        .forEach(function (element) {
          const $select = $(element);
          const value = $select.val();
          const label = $select.find('option:selected').text();

          // Read the persisted filter value from the hidden field and apply it.
          const currentFilter = $('input[name="field_participant_group_filter[0][value]"]').val();
          participationListHideShow($select, currentFilter);

          // Register a radio option for any pre-selected (non-empty) group.
          if (value && value !== '_none' && !selectedGroupValues.includes(value)) {
            selectedGroupValues.push(value);
            addParticipantGroupFilterOption(value, label, value === currentFilter);
          }

          // When the user picks a new group for this participant, register it.
          $select.on('change', function () {
            const newValue = $(this).val();
            const newLabel = $(this).find('option:selected').text();

            if (newValue && newValue !== '_none' && !selectedGroupValues.includes(newValue)) {
              selectedGroupValues.push(newValue);
              addParticipantGroupFilterOption(newValue, newLabel, newValue === currentFilter);
            }
          });

        });

      // Listen for radio button changes within the participants tab group.
      // On change: persist the selected value to the hidden field and
      // re-evaluate visibility for every participant row.
      once('detail-group-participants-wrapper', '#edit-group-participants', context)
        .forEach(function (element) {
          const $wrapper = $(element);

          $wrapper.on('change', 'input[name="training_participant_group_filter"]', function () {
            const currentFilterValue = $(this).val();

            // Write the selected filter back to the underlying hidden/text field.
            $wrapper
              .find('input[name="field_participant_group_filter[0][value]"]')
              .val(currentFilterValue)
              .trigger('change');

            // Re-evaluate visibility for every participant row.
            $wrapper
              .find('#edit-field-participants-wrapper .paragraph-type--participant select[data-drupal-selector$="subform-field-participant-group"]')
              .each(function () {
                participationListHideShow($(this), currentFilterValue);
              });
          });
        });

    },

  };

})(jQuery, Drupal, drupalSettings, once);