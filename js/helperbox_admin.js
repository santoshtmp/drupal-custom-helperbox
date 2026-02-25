/**
 * @file
 * Helperbox admin behaviors.
 *
 * Provides admin UI enhancements for helperbox module including
 * tab hash navigation and contextual links toggle.
 */

(function ($, Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Attach helperbox admin behaviors.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches helperbox admin functionality.
   */
  Drupal.behaviors.helperboxAdmin = {
    attach: function (context) {
      // List of form IDs to apply tab hash navigation.
      var formIds = [
        'config-pages-site-settings-form'
      ];

      // Apply tab hash navigation for each form.
      formIds.forEach(function (formId) {
        once('helperbox-admin', '#' + formId, context).forEach(function () {
          var $form = $('#' + formId);

          // Scroll to tab if hash exists in URL.
          if (window.location.hash) {
            var $target = $form.find('.horizontal-tabs-list a[href="' + window.location.hash + '"]');
            if ($target.length) {
              $target.trigger('click');
              $('html, body').animate({
                scrollTop: $target.offset().top - 100
              }, 400);
            }
          }

          // Update URL hash when tab is clicked.
          $form.find('ul.horizontal-tabs-list li a').on('click', function (event) {
            var href = $(this).attr('href');
            var tabLink = window.location.pathname + window.location.search + href;
            window.history.replaceState(null, null, tabLink);
          });
        });
      });

      // Handle contextual links toggle for render block fields.
      once('helperbox-admin', '.edit-field-helperbox-renderblock', context).forEach(function (element) {
        var $element = $(element);
        $element.find('.contextual.edit-adminlinks .trigger').on('click', function () {
          $element.find('.edit-adminlinks').toggleClass('open');
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings, once);
