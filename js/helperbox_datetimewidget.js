/**
 * @file
 * Helperbox datetime widget behaviors.
 *
 * Initializes Select2 on datetime widget select elements.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Attach helperbox datetime widget behaviors.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches select2 initialization for datetime widget.
   */
  Drupal.behaviors.helperboxDatetimeWidget = {
    attach: function (context) {
      once('helperbox-datetime-widget', '.fieldset-helperbox-date-time-widget select', context).forEach(function (element) {
        $(element).select2({
          width: '100px'
        });
      });
    }
  };

})(jQuery, Drupal, once);
