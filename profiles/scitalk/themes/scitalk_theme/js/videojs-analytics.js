/**
 *  Combined and modified the scripts from:
 *       https://www.npmjs.com/package/videojs-analytics
 *  and
 *       https://github.com/tankvn/videojs-google-analytics/blob/main/dist/videojs-google-analytics.js
 *  to make it work for us at PI.
 *  grabbed only the js script
 */

/**
 * videojs-analytics
 * @version 1.0.0
 * @copyright 2017 Adam Oliver <mail@adamoliver.net>
 * @license MIT
 */
(function (f) {
  if (typeof exports === "object" && typeof module !== "undefined") {
    module.exports = f();
  } else if (typeof define === "function" && define.amd) {
    define([], f);
  } else {
    var g;
    if (typeof window !== "undefined") {
      g = window;
    } else if (typeof global !== "undefined") {
      g = global;
    } else if (typeof self !== "undefined") {
      g = self;
    } else {
      g = this;
    }
    g.videojsAnalytics = f();
  }
})(function () {
  var define, module, exports;
  return (function e(t, n, r) {
    function s(o, u) {
      if (!n[o]) {
        if (!t[o]) {
          var a = typeof require == "function" && require;
          if (!u && a) return a(o, !0);
          if (i) return i(o, !0);
          var f = new Error("Cannot find module '" + o + "'");
          throw ((f.code = "MODULE_NOT_FOUND"), f);
        }
        var l = (n[o] = { exports: {} });
        t[o][0].call(
          l.exports,
          function (e) {
            var n = t[o][1][e];
            return s(n ? n : e);
          },
          l,
          l.exports,
          e,
          t,
          n,
          r
        );
      }
      return n[o].exports;
    }
    var i = typeof require == "function" && require;
    for (var o = 0; o < r.length; o++) s(r[o]);
    return s;
  })(
    {
      1: [
        function (require, module, exports) {
          (function (global) {
            "use strict";

            exports.__esModule = true;

            const _video =
              typeof window !== "undefined"
                ? window["videojs"]
                : typeof global !== "undefined"
                ? global["videojs"]
                : null;

            const _video2 = _interopRequireDefault(_video);

            function _interopRequireDefault(obj) {
              return obj && obj.__esModule ? obj : { default: obj };
            }

            // Default options for the plugin.
            const defaults = {
              events: [],
              assetName: "Video",
              defaultVideoCategory: "Video",
              defaultAudioCategory: "Audio",
            };

            // window.ga = window.ga || function () {
            //   return void 0;
            // };

            /**
             * A video.js plugin.
             *
             * In the plugin function, the value of `this` is a video.js `Player`
             * instance. You cannot rely on the player being in a "ready" state here,
             * depending on how the plugin is invoked. This may or may not be important
             * to you; if not, remove the wait for "ready"!
             *
             * @function analytics
             * @param    {Object} [options={}]
             *           An object of options left to the plugin author to define.
             */
            const analytics = function analytics(options) {
              const _this = this;

              options = _video2.default.mergeOptions(defaults, options);

              this.ready(function () {
                const progress = {
                  quarter: false,
                  half: false,
                  threeQuarters: false,
                };

                function formatSeconds(seconds) {
                  const result = new Date(seconds * 1000)
                    .toISOString()
                    .slice(11, 19);
                  return result;
                }

                function track(player, action, label) {
                  let category = options.defaultVideoCategory;

                  if (player.isAudio()) {
                    category = options.defaultAudioCategory;
                  }

                  if (!label) {
                    label = "";
                  }

                  // window.ga('send', 'event', category, action, label);

                  const elapsed = Math.round(player.currentTime());
                  const duration = Math.round(player.duration());
                  const percent = Math.round((elapsed / duration) * 100);

                  gtag("event", action, {
                    event_category: category,
                    view: 1,
                    time: formatSeconds(elapsed),
                    duration: formatSeconds(duration),
                    percentage: percent,
                    label: label,
                  });
                }

                function play(player, event) {
                  track(player, event.action, event.label);
                  track(player, "Asset name", options.assetName);
                }

                function pause(player, event) {
                  track(player, event.action, event.label);
                }

                function ended(player, event) {
                  track(player, event.action, event.label);
                }

                function fullscreenchange(player, event) {
                  const label = player.isFullscreen()
                    ? event.label.open
                    : event.label.exit;

                  track(player, event.action, label);
                }

                function resolutionchange(player, event) {
                  let resolution = {
                    label: "",
                  };

                  // It's possible that resolutionchange is used as an event where
                  // the video object doesn't have currentResolution
                  // so we need to check for it's existance first.
                  if (player.currentResolution) {
                    resolution = player.currentResolution();
                  }
                  const label = resolution.label ? resolution.label : "Default";

                  track(player, event.action, label);
                }

                function timeupdate(player, event) {
                  const elapsed = Math.round(player.currentTime());
                  const duration = Math.round(player.duration());
                  const percent = Math.round((elapsed / duration) * 100);

                  if (!progress.quarter && percent > 25) {
                    track(player, event.action, "Complete 25%");
                    progress.quarter = true;
                  }

                  if (!progress.half && percent > 50) {
                    track(player, event.action, "Complete 50%");
                    progress.half = true;
                  }

                  if (!progress.threeQuarters && percent > 75) {
                    track(player, event.action, "Complete 75%");
                    progress.threeQuarters = true;
                  }
                }

                function handleEvent(player, event) {
                  track(player, event.action, event.label);
                }

                function getEvent(eventName) {
                  return options.events.filter(function (event) {
                    return event.name === eventName;
                  })[0];
                }

                // Set up the custom event tracking that won't use handleEvents

                const eventNames = options.events.map(function (event) {
                  return event.name || event;
                });

                if (eventNames.indexOf("play") > -1) {
                  const playEvent = getEvent("play");

                  _this.on("play", function () {
                    play(this, playEvent);
                  });
                  options.events = options.events.filter(function (event) {
                    return event.name !== "play";
                  });
                }

                if (eventNames.indexOf("pause") > -1) {
                  const pauseEvent = getEvent("pause");

                  _this.on("pause", function () {
                    pause(this, pauseEvent);
                  });
                  options.events = options.events.filter(function (event) {
                    return event.name !== "pause";
                  });
                }

                if (eventNames.indexOf("ended") > -1) {
                  const endedEvent = getEvent("ended");

                  _this.on("ended", function () {
                    ended(this, endedEvent);
                  });
                  options.events = options.events.filter(function (event) {
                    return event.name !== "ended";
                  });
                }

                if (eventNames.indexOf("resolutionchange") > -1) {
                  const resolutionchangeEvent = getEvent("resolutionchange");

                  _this.on("resolutionchange", function () {
                    resolutionchange(this, resolutionchangeEvent);
                  });
                  options.events = options.events.filter(function (event) {
                    return event.name !== "resolutionchange";
                  });
                }

                if (eventNames.indexOf("fullscreenchange") > -1) {
                  const fullscreenEvent = getEvent("fullscreenchange");

                  _this.on("fullscreenchange", function () {
                    fullscreenchange(this, fullscreenEvent);
                  });
                  options.events = options.events.filter(function (event) {
                    return event.name !== "fullscreenchange";
                  });
                }

                if (eventNames.indexOf("timeupdate") > -1) {
                  const timeupdateEvent = getEvent("timeupdate");

                  _this.on("timeupdate", function () {
                    timeupdate(this, timeupdateEvent);
                  });
                  options.events = options.events.filter(function (event) {
                    return event.name !== "timeupdate";
                  });
                }

                // For any other event that doesn't require special processing
                // we will use the handleEvent event handler

                const _loop = function _loop() {
                  if (_isArray) {
                    if (_i >= _iterator.length) return "break";
                    _ref = _iterator[_i++];
                  } else {
                    _i = _iterator.next();
                    if (_i.done) return "break";
                    _ref = _i.value;
                  }

                  const event = _ref;

                  _this.on(event.name, function () {
                    handleEvent(this, event);
                  });
                };

                for (
                  var _iterator = options.events,
                    _isArray = Array.isArray(_iterator),
                    _i = 0,
                    _iterator = _isArray
                      ? _iterator
                      : _iterator[Symbol.iterator]();
                  ;

                ) {
                  let _ref;

                  const _ret = _loop();

                  if (_ret === "break") break;
                }
              });
            };

            // Register the plugin with video.js.
            //_video2.default.plugin('analytics', analytics);
            _video2.default.registerPlugin("analytics", analytics);

            // Include the version number.
            analytics.VERSION = "1.0.0";

            exports.default = analytics;
          }).call(
            this,
            typeof global !== "undefined"
              ? global
              : typeof self !== "undefined"
              ? self
              : typeof window !== "undefined"
              ? window
              : {}
          );
        },
        {},
      ],
    },
    {},
    [1]
  )(1);
});
