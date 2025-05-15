// initialize the preroll for videos
if (document.getElementById("scitalk_video_js")) {
  const player = videojs.getPlayer("scitalk_video_js") || videojs("scitalk_video_js");
  const preroll_video_url = drupalSettings.preroll_video_url || false;
  if (player && preroll_video_url) {
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
