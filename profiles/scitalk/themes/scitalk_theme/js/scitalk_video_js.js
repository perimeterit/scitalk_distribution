//Video.js Player config and control
(function ($, Drupal, once, drupalSettings) {
  Drupal.behaviors.sciTalkVideoJS = {
    attach: function (context, settings) {
      // $('#video_player', context).once().each(function() {
      $(once("video_js_attached", "#video_player", context)).each(function () {
        // const has_video = $("#scitalk_video_js").length;
        // if (!has_video) return;

        const options = null; //{};
        videojs("scitalk_video_js", options, function () {
          const search = window.location.search;
          const params = new URLSearchParams(search);
          let offset = search ? Number(params.get("t")) : false;

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

          // const talk_src = this.currentSources();
          // console.log(talk_src);
          // // try to set offset time when set, after playing preroll but it's not working..
          // this.on("adend", () => {
          //   offset = search ? Number(params.get("t")) : false;
          //   if (offset) {
          //     // this.pause();
          //     console.log("ad ended here", offset);
          //     // this.ads.skipLinearAdMode();
          //     // this.src(talk_src);
          //     this.trigger("play");
          //     console.log(this.currentSources());
          //     console.log(this.currentSource());
          //     console.log(this.currentSource(), this.currentSrc());

          //     // this.currentTime(offset);
          //     // this.play();
          //   }
          // });

          const controlBar = this.getChild("ControlBar");

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

          controlBar.addChild("copyUrlButton", {});

          //move the button before the Picture-in-Picture button:
          try {
            this.controlBar
              .el()
              .insertBefore(
                controlBar.getChild("copyUrlButton").el(),
                controlBar.getChild("pictureInPictureToggle").el()
              );
          } catch (e) {}

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
