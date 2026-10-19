document.addEventListener("DOMContentLoaded", () => {
  //check if there's a video start time parameter in the url to play the video from that point:
  const search = window.location.search;
  const params = new URLSearchParams(search);
  let offset = search ? Number(params.get("t")) : false;

  // Get the selected language from the dropdown
  const langSelect = document.getElementById("transcripts-language-selection");
  let lang = langSelect ? langSelect.value : "en";
  let transcriptWrapper = document.getElementById("transcript-" + lang);

  // get all transcripts for all languages, so we can add listeners to the time jump buttons inside the transcripts:
  const allTranscriptsWrapper = document.getElementById("talk-transcripts");

  const player = videojs("scitalk_video_js");

  // sync times between player and transcript text so that we could skip to a specific
  // time on the video when clicking on a time stamp in the transcript:
  function syncPlayingTimes() {
    const jumpToBtns = allTranscriptsWrapper.querySelectorAll(
      ".transcript-time-jump",
    );
    jumpToBtns.forEach((jumpTo) => {
      jumpTo.addEventListener("click", function (e) {
        offset = this.value; //e.target.value;
        if (offset) {
          player.currentTime(offset);
          player.play();
        }
      });
    });
  }

  // sync player and transcript times on load
  syncPlayingTimes();

  // two ways of Highlighting the current text, either:
  //  1. use the "timeupdate" event on the player, or
  //  2. find the tracks and listen for the "cuechange" event
  // option 1 highlights faster than what the video is showing. It's better when another lang is selected under CC. Here we need to find the element with id = timestamp
  // option 2 highlights the text on time, it's more accurate. Here have to search for text on the arial-label to match the text.
  //    BUT!!!
  //      - it only works when the captions are on so I have to force the caption so at least "hidden"
  //      - Safari, Opera show the text in the aria-label escaped and to find the text on these cases i have to look inside the span

  (function () {
    let prevHighlightedText = null;

    // Option 1: listen for the timeupdate event from the video player
    player.on("timeupdate", (event) => {
      // Update the language
      let lang = langSelect ? langSelect.value : "en";
      let transcriptWrapper = document.getElementById("transcript-" + lang);

      const curTime = parseInt(player.currentTime());

      if (curTime == 0) {
        return;
      }

      // Ids are in the form 'transcript-item-' + language + '-' + seconds
      const el = document.getElementById(
        "transcript-item-" + lang + "-" + `${curTime}`,
      );

      // If we have an element that matches the current timestamp
      // highlight the text and scroll it to the top.
      if (el) {
        if (prevHighlightedText) {
          prevHighlightedText.classList.toggle("transcript-highlight");
        }

        // The transcript text element (dd)
        const transcriptText = el.nextElementSibling;
        // Reset the previous highlighted text value
        prevHighlightedText = transcriptText;

        transcriptWrapper.scrollTo({
          // Scroll to the play button above the highlighted text
          top: el.offsetTop - transcriptWrapper.offsetTop,
          behavior: "smooth",
        });
        transcriptText.classList.toggle("transcript-highlight");
      }
    });

    //////////////////

    // Option 2: listen for the "cuechange" event on the track:

    // //need to wait until tracks are loaded
    // player.on("loadedmetadata", function () {
    //   let tracks = player.textTracks();
    //   for (let i = 0; i < tracks.length; i++) {
    //     const track = tracks[i];
    //     const captionLanguage = track.language;
    //     //only english??
    //     if (captionLanguage != "en") {
    //       continue;
    //     }

    //     // if the captions are disabled then the cuechange won't trigger. So use this we need them either "hidden" or "showing"
    //     // let's force it hidden if disabled
    //     if (track.mode == "disabled") {
    //       track.mode = "hidden";
    //     }

    //     track.addEventListener("cuechange", (event) => {
    //       if (prevHighlightedText) {
    //         prevHighlightedText.classList.toggle("transcript-highlight");
    //       }

    //       const active = track.activeCues[0];
    //       // const startTime = active.startTime;
    //       // const endTime = active.endTime;
    //       const activeText = active.text.trim();
    //       const search_text = `div[aria-label="${activeText}"]`;
    //       let textElWrapper = document.querySelector(search_text);

    //       // if i couldn't find the above it's probably because of the apostrophes being escaped, so try find the actual text:
    //       if (!textElWrapper) {
    //         const text_span_el = [
    //           ...document.querySelectorAll(".trans_text span"),
    //         ].filter((el) => el.innerText.trim() == activeText);
    //         textElWrapper = text_span_el[0].parentElement; //return the parent div
    //       }

    //       const curText = textElWrapper.firstChild;
    //       prevHighlightedText = curText;

    //       //this scolls inside the subtitles element to the current text
    //       transcriptWrapper.scrollTo({
    //         top:
    //           textElWrapper.parentElement.offsetTop - transcriptWrapper.offsetTop, //scroll to the play button above the highlighted text
    //         // top: textElWrapper.offsetTop - wrap.offsetTop, //scroll to the highlighted text
    //         behavior: "smooth",
    //       });
    //       curText.classList.toggle("transcript-highlight");
    //     });
    //   }
    // });
  })();
});
