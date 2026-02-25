/**
 * @file
 * Helperbox JSON Editor behavior.
 *
 * Transforms textarea fields into JSON editors with syntax highlighting
 * and real-time validation using Ace Editor.
 */

(function ($, Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Store editor instances.
   */
  var editorInstances = {};

  /**
   * Initialize Ace Editor for a textarea.
   *
   * @param {jQuery} $textarea
   *   The textarea jQuery element.
   */
  function initializeEditor($textarea) {
    if ($textarea.length === 0) {
      return;
    }

    var textareaId = $textarea.attr('id');
    if (!textareaId) {
      // Generate unique ID if not present.
      textareaId = 'helperbox-json-editor-' + Math.random().toString(36).substr(2, 9);
      $textarea.attr('id', textareaId);
    }

    // Skip if already initialized.
    if (editorInstances[textareaId]) {
      return;
    }

    // Create wrapper div.
    var $wrapper = $('<div class="helperbox-json-editor-wrapper"></div>');
    $textarea.wrap($wrapper);

    // Create editor container.
    var editorId = textareaId + '-ace-editor';
    var $editorContainer = $('<div id="' + editorId + '" class="helperbox-json-editor"></div>');
    $textarea.before($editorContainer);

    // Add resize handle listener.
    var resizeObserver = new ResizeObserver(function() {
      editor.resize();
    });
    resizeObserver.observe($editorContainer[0]);

    // Add status indicator.
    var $status = $('<span class="helperbox-json-status"></span>');
    $wrapper.append($status);

    // Add format button.
    var $formatBtn = $('<button type="button" class="helperbox-json-format-btn">' + Drupal.t('Format JSON') + '</button>');
    $wrapper.append($formatBtn);

    // Hide original textarea but keep it for form submission.
    $textarea.addClass('helperbox-json-textarea');

    // Initialize Ace Editor.
    var editor = ace.edit(editorId);
    editor.setTheme('ace/theme/monokai');
    editor.session.setMode('ace/mode/json');
    editor.session.setUseWrapMode(true);
    editor.setShowPrintMargin(false);
    editor.setOptions({
      fontSize: '14px',
      showLineNumbers: true,
      tabSize: 2,
      useSoftTabs: true,
      wrapBehavioursEnabled: true,
      autoScrollEditorIntoView: true
    });

    // Set initial value from textarea.
    var initialValue = $textarea.val();
    if (initialValue) {
      editor.setValue(initialValue, -1);
    }

    // Store editor instance.
    editorInstances[textareaId] = {
      editor: editor,
      $textarea: $textarea,
      $status: $status,
      $wrapper: $wrapper
    };

    // Sync editor content to textarea on change.
    editor.session.on('change', function() {
      var value = editor.getValue();
      $textarea.val(value);

      // Validate JSON in real-time.
      validateJSON(textareaId, value);
    });

    // Format button click handler.
    $formatBtn.on('click', function() {
      formatJSON(textareaId);
    });

    // Initial validation.
    if (initialValue) {
      validateJSON(textareaId, initialValue);
    }
  }

  /**
   * Validate JSON content.
   *
   * @param {string} textareaId
   *   The textarea element ID.
   * @param {string} value
   *   The JSON string to validate.
   */
  function validateJSON(textareaId, value) {
    var instance = editorInstances[textareaId];
    if (!instance) {
      return;
    }

    var $status = instance.$status;
    var $editorContainer = instance.$wrapper.find('.helperbox-json-editor');

    // Empty value is valid (optional field).
    if (!value || value.trim() === '') {
      $status.removeClass('visible valid invalid');
      $editorContainer.removeClass('has-error is-valid');
      return;
    }

    try {
      JSON.parse(value);
      // Valid JSON.
      $status.text(Drupal.t('Valid JSON')).removeClass('invalid').addClass('visible valid');
      $editorContainer.removeClass('has-error').addClass('is-valid');
    } catch (e) {
      // Invalid JSON.
      $status.text(Drupal.t('Invalid JSON')).removeClass('valid').addClass('visible invalid');
      $editorContainer.removeClass('is-valid').addClass('has-error');
    }
  }

  /**
   * Format JSON content.
   *
   * @param {string} textareaId
   *   The textarea element ID.
   */
  function formatJSON(textareaId) {
    var instance = editorInstances[textareaId];
    if (!instance) {
      return;
    }

    var editor = instance.editor;
    var value = editor.getValue();

    if (!value || value.trim() === '') {
      return;
    }

    try {
      var parsed = JSON.parse(value);
      var formatted = JSON.stringify(parsed, null, 2);
      editor.setValue(formatted, -1);
    } catch (e) {
      Drupal.message(Drupal.t('Cannot format invalid JSON. Please fix errors first.'), {
        type: 'error'
      });
    }
  }

  /**
   * Get value from editor for form validation.
   *
   * @param {string} textareaId
   *   The textarea element ID.
   * @returns {string}
   *   The editor value.
   */
  function getEditorValue(textareaId) {
    var instance = editorInstances[textareaId];
    if (instance) {
      return instance.editor.getValue();
    }
    return $('#' + textareaId).val();
  }

  // Attach behavior.
  Drupal.behaviors.helperboxJsonEditor = {
    attach: function(context) {
      // Initialize editors for each JSON field with the helperbox-json-editor-field class.
      once('helperbox-json-editor', '.helperbox-json-editor-field', context).forEach(function(element) {
        var $textarea = $(element);
        
        // Load Ace Editor from CDN if not already loaded.
        if (typeof ace === 'undefined') {
          $.getScript('https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.6/ace.min.js', function() {
            initializeEditor($textarea);
          });
        } else {
          initializeEditor($textarea);
        }
      });
    }
  };

  // Override form validation to use editor values.
  Drupal.behaviors.helperboxJsonEditorValidation = {
    attach: function(context) {
      once('helperbox-json-validation', 'form#helperbox-settings-form', context).forEach(function() {
        var $form = $(this);
        $form.on('submit', function(e) {
          // Ensure all editor values are synced to textareas.
          Object.keys(editorInstances).forEach(function(textareaId) {
            var instance = editorInstances[textareaId];
            if (instance) {
              instance.$textarea.val(instance.editor.getValue());
            }
          });
        });
      });
    }
  };

  // Expose functions globally for external use.
  Drupal.helperboxJsonEditor = {
    initializeEditor: initializeEditor,
    validateJSON: validateJSON,
    formatJSON: formatJSON,
    getEditorValue: getEditorValue,
    editors: editorInstances
  };

})(jQuery, Drupal, drupalSettings, once);
