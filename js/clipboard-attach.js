/**
 * @file
 * Citation Select behaviors.
 */

(function ($, Drupal) {

  'use strict';

  const bibliographyBodySelector = '#formatted-bibliography .csl-bib-body';

  /**
   * Attach clipboard.js to button.
   */
  Drupal.behaviors.citationSelect = {
    attach: function (context, settings) {
      const clipboard = new ClipboardJS('.clipboard-button', {
        text: function (trigger) {
          const body = document.querySelector(bibliographyBodySelector);
          return body ? body.innerText.trim() : '';
        },
      });

      clipboard.on('success', function () {
          const body = document.querySelector(bibliographyBodySelector);
          if (!body) {
            return;
          }
          const range = document.createRange();
          range.selectNodeContents(body);
          const selection = window.getSelection();
          selection.removeAllRanges();
          selection.addRange(range);
        });
    }
  };
} (jQuery, Drupal));
