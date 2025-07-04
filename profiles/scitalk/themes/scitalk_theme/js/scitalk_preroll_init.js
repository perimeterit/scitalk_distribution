// initialize the preroll for videos
(function () {
  if (document.getElementById("scitalk_video_js")) {
    const player = videojs.getPlayer("scitalk_video_js") || videojs("scitalk_video_js");
    const preroll_video_url = drupalSettings.preroll_video_url || false;
    const show_preroll = drupalSettings.show_preroll;
    if (player && preroll_video_url && show_preroll) {
      try {
        player.preroll({
          src: preroll_video_url,
          //   src: { type: "application/x-mpegURL", src: preroll_video_url },
          lang: {
            skip: "Skip",
            "skip in": "Skip in ",
          },
        });
      } catch (e) {}
    }
  }
})();

// this code doesn't work sometimes: the preroll doesn't get initialize on time and this error shows up:
// video.min.js?swq462:12
// VIDEOJS: ERROR: videojs-contrib-ads has not seen a loadstart event 5 seconds after being initialized, but a source is present.
// This indicates that videojs-contrib-ads was initialized too late. It must be initialized immediately after video.js in the same tick.
// As a result, some ads will not play and some media events will be incorrect.
// For more information, see http://videojs.github.io/videojs-contrib-ads/integrator/getting-started.html

// (function ($, Drupal, drupalSettings) {
//   Drupal.behaviors.videjojsPreroll = {
//     attach: function (context, settings) {
//       // initialize the preroll for videos
//       if (document.getElementById("scitalk_video_js")) {
//         const player = videojs.getPlayer("scitalk_video_js") || videojs("scitalk_video_js");
//         const show_preroll = drupalSettings.show_preroll;
//         const preroll_video_url = drupalSettings.preroll_video_url || false;

//         //check if we've already added preroll to the player
//         const prerollAdded = Object.hasOwn(player, "preroll");
//         if (player && preroll_video_url && show_preroll && !prerollAdded) {
//           try {
//             player.preroll({
//               src: preroll_video_url,
//               //   src: { type: "application/x-mpegURL", src: preroll_video_url },
//               lang: {
//                 skip: "Skip",
//                 "skip in": "Skip in ",
//               },
//             });
//           } catch (e) {}
//         }
//       }
//     },
//   };
// })(jQuery, Drupal, drupalSettings);
