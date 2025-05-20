//Video.js Player config and control
(function ($, Drupal, once, drupalSettings) {
  Drupal.behaviors.sciTalkVideoJS = {
    attach: function (context, settings) {
      $(once("video_js_attached", "#video_player", context)).each(function () {
        const options = null; //{};
        videojs("scitalk_video_js", options, function () {
          const search = window.location.search;
          const params = new URLSearchParams(search);
          let offset = search ? Number(params.get("t")) : false;

          const controlBar = this.getChild("ControlBar");
          const picToggle = controlBar.getChild("pictureInPictureToggle");
          const picToggleIndex = controlBar.children().indexOf(picToggle);

          // ddev composer require npm-asset/videojs-quality-selector-hls
          this.qualitySelectorHls({
            placementIndex: picToggleIndex - 1,
            vjsIconClass: "vjs-icon-cog",
            // displayCurrentQuality: true, //this would show the selected quality on the control bar
          });

          const copyUrlToClipboard = () => {
            const current_offset = this.currentTime();
            const url = new URL(window.location.href);
            url.searchParams.set("t", parseInt(current_offset, 10));
            const copied_url = url.href || window.location.href;
            navigator.clipboard.writeText(copied_url);
          };

          //when video starts to play, move the time to the offset value
          //  (on the first time it'd be at 0. When user copies the url at a time it'd be that time offset)
          this.on("play", () => {
            if (offset) {
              this.currentTime(offset);
            }
            offset = false;
          });

          // when the preroll ad ends (including skipped), if there's an offset time to start the video from,
          // then try to set offset time on the talk video
          this.on("adend", () => {
            //read the offset time
            offset = search ? Number(params.get("t")) : false;
            if (offset) {
              //set the current time to offset on the talk video which livesin this.ads.snapshot.currentTime
              this.ads.snapshot.currentTime = offset;

              // firefox is not setting the offset time. According to videojs-contrib-ads:
              // in some browsers (firefox) `canplay` may not fire correctly.
              // Reace the `canplay` event with a timeout.
              if (navigator.userAgent.includes("Firefox")) {
                this.ads.endLinearAdMode();
                const p = this;
                setTimeout(function () {
                  p.ads.snapshot.currentTime = offset;
                  p.trigger("contentcanplay");
                  p.currentTime(offset);
                }, 1000);
              }
            }
          });

          //display current time
          const displayCurrentTime = controlBar.getChild("currentTimeDisplay").el();
          $(displayCurrentTime).show();

          const playerInstance = this;
          //create a button in the control bar to copy url at current time
          const Button = videojs.getComponent("Button");

          //new way to extend component, see: https://videojs.com/guides/videojs-7-to-8/
          class CopyUrlButton extends Button {
            constructor(player, options) {
              super(player, options);
              this.addClass("normal-stream");
              this.setAttribute("title", "Copy Video URL at current time");
            }
            handleClick() {
              if (!playerInstance.paused()) {
                playerInstance.pause();
              }
              copyUrlToClipboard();
            }
            buildCSSClass() {
              return "vjs-icon-copy-url vjs-control vjs-button";
            }
          }
          videojs.registerComponent("copyUrlButton", CopyUrlButton);

          //move the copyUrlButton before the Picture-in-Picture button:
          controlBar.addChild("copyUrlButton", {}, picToggleIndex);
          // try {
          //   this.controlBar
          //     .el()
          //     .insertBefore(
          //       controlBar.getChild("copyUrlButton").el(),
          //       controlBar.getChild("pictureInPictureToggle").el()
          //     );
          // } catch (e) {}

          //adding google Analytics to record videojs events (play, pause, complete, ,time updated):
          this.analytics({
            events: [
              {
                name: "play",
                label: "video_play",
                action: "play",
              },
              {
                name: "pause",
                label: "video_pause",
                action: "pause",
              },
              {
                name: "ended",
                label: "video_ended",
                action: "completed", //'ended',
              },
              {
                name: "timeupdate",
                action: "time updated",
              },
            ],
          });
        });

        //disable context menu on right click
        $("video").bind("contextmenu", function (e) {
          return false;
        });
      });
    },
  };
})(jQuery, Drupal, once, drupalSettings);
