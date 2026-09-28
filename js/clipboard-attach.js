/**
 * @file
 * Citation Select behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attach clipboard.js to button.
   */
  Drupal.behaviors.citationSelect = {
    attach: function (context, settings) {
      new ClipboardJS('.clipboard-button', {
        text: function (trigger) {
          const text = document.querySelector('#formatted-bibliography').innerText.trim();
          const suffix = 'Review all citations for accuracy.';

          // Strip unwanted suffix if present
          if (text.endsWith(suffix)) {
            return text.slice(0, -suffix.length).trim();
          }
          return text;
        }
      });
    }
  };

} (jQuery, Drupal));
