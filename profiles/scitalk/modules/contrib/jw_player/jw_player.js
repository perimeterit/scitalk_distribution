/**
 * @file
 * JW Player initialization.
 */

/**
 * Initializes jw player instances based on their settings.
 *
 */
(function ($, Drupal, drupalSettings, jwplayer) {
  'use strict';

  Drupal.behaviors.jw_player = {
    attach: function (context) {

      if (drupalSettings.jw_player) {
        if (typeof drupalSettings.jw_player.license_key != 'undefined') {
          jwplayer.key = drupalSettings.jw_player.license_key;
        }
        if (drupalSettings.jw_player.players) {
          var position;
          for (position in drupalSettings.jw_player.players) {
            if (drupalSettings.jw_player.players.hasOwnProperty(position)) {
              $(context).find('#' + position).once('jw-player').each(function (index, element) {
                jwplayer(position).setup(drupalSettings.jw_player.players[position]);
              });
            }
          }
        }
      }
    }
  };

}(jQuery, Drupal, drupalSettings, jwplayer));
