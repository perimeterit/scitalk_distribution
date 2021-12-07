//JW Player config and control
(function($, Drupal, drupalSettings) {
  
  Drupal.behaviors.scitalkMediaJWPlayer = {
    attach: function (context, settings) {
      //add a "copy video url at current time" button
      $('#video_player', context).once().each(function() {
        const search = window.location.search;
        const params = new URLSearchParams(search);
        let offset = search ? Number(params.get("t")) : false;
  
        const copyUrlToClipboard = () => {
          const current_offset = playerInstance.getPosition();
          const url = new URL(window.location.href);
          url.searchParams.set('t', parseInt(current_offset, 10));
          const copied_url = url.href || window.location.href;
          navigator.clipboard.writeText(copied_url);
        };

        const icon = drupalSettings.icon_path + drupalSettings.url_copy_icon;
        const playerInstance = jwplayer('video_player');
        playerInstance.addButton(icon,'Copy Video URL at current time', () => {
          if (playerInstance.getState() == 'playing') {
            playerInstance.pause();
          }
          copyUrlToClipboard();
        },'copy-video-url');
  
        playerInstance.on('firstFrame', () => {
          if (offset) {
            playerInstance.seek(offset);
          }
          offset = false;
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
