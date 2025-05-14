// document.addEventListener("DOMContentLoaded", () => {
// this is how to get the preroll to initialize
document.getElementById("scitalk_video_js") &&
  videojs("scitalk_video_js", null, function () {
    const player = this;
    //get the preroll video from configs
    const preroll_video_url = drupalSettings.preroll_video_url || false;
    if (preroll_video_url) {
      player.one("ready", function () {
        try {
          this.preroll({
            src: preroll_video_url,
            //   src: { type: "application/x-mpegURL", src: preroll_video_url },
            // src: [
            //   {
            //     type: "application/vnd.apple.mpegurl",
            //     src: preroll_video_url,
            //   },
            // ],
            lang: {
              skip: "Skip",
              "skip in": "Skip in ",
            },
          });
        } catch (e) {}
      });
    }
  });
// });
