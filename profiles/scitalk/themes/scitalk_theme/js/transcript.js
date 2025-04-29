document.addEventListener("DOMContentLoaded", () => {
  const hasTranscript = document.querySelector(".transcript-text-wrapper");
  if (!hasTranscript) {
    return;
  }

  const transcriptModal = document.querySelector(".transcript-modal");
  // const transcriptModalBtn = document.querySelector(".transcript-modal-close");
  const pageContentWrapper = document.querySelector(".content-wrapper");
  const transcriptModalHeader = document.querySelector(
    ".transcript-modal-header"
  );
  const sideTranscriptWrapper = "video-trans-wrapper";
  const transcriptWrapper = document.getElementById(
    "formatted-transcript-text"
  );

  const cutoffWidth = 1100; // width to determine whether to display left or bottom transcript section
  let isTranscriptShowing = false;

  //close Transcript modal
  transcriptModalHeader.addEventListener("click", () => {
    transcriptModal.style.display = "none";
    toggleTranscript.click();
  });

  // need to add a Close button for the side Transcript
  const sideCloseButton = (function () {
    let sideWrap = null;
    return {
      create: function () {
        if (!sideWrap) {
          sideWrap = document.createElement("div");
          sideWrap.id = "side-wrap";
          const cBox = document.createElement("div");
          cBox.className = "close-side";
          cBox.innerHTML =
            '<span class="close-side-btn">×</span><h2>Hide Transcript</h2>';
          sideWrap.append(cBox);

          cBox.addEventListener("click", () => {
            sideWrap.style.display = "none";
            transcriptModalHeader.click();
          });
        }
        sideWrap.style.display = "block";
        return sideWrap;
      },
      remove: function () {
        sideWrap?.remove();
        sideWrap = null;
      },
      hide: function () {
        if (sideWrap) {
          sideWrap.style.display = "none";
        }
      },
    };
  })();

  // toggle the side Transcript section
  function toggleSideTranscript() {
    // side transcript section showing - add transcript:
    if (pageContentWrapper.classList.contains(sideTranscriptWrapper)) {
      // if a big screen:
      if (window.innerWidth > cutoffWidth) {
        //create the side close button element wrapper (includes a wrapper for both the button and the trascript text)
        const wrap = sideCloseButton.create();
        pageContentWrapper.append(wrap);
        wrap.append(transcriptWrapper);

        const talkNodeEl = document.querySelector(".node.talk");
        const talkNodeElDimensions = talkNodeEl.getBoundingClientRect();
        const posTransTop = talkNodeElDimensions.y - talkNodeEl.y;
        const posTransHeight = talkNodeElDimensions.height;

        transcriptWrapper.style.marginTop = `${posTransTop}px`;
        transcriptWrapper.style.height = `${posTransHeight}px`;
        transcriptWrapper.style.display = "block";
      }
    } else {
      transcriptWrapper.style.display = "none";
      sideCloseButton.hide();
    }
  }

  // toggle bottom Transcript section
  function toggleBottomTranscript() {
    const transcriptContent = document.querySelector(
      ".transcript-modal-content"
    );
    const transcriptModalContent = document.querySelector(
      ".transcript-modal-body"
    );
    const transcriptWrapper = document.getElementById(
      "formatted-transcript-text"
    );

    if (isTranscriptShowing) {
      if (window.innerWidth <= cutoffWidth) {
        // scroll to the top first: when there are talk metadata (speakers, sci areas, etc) and long abstracts the location
        // of the video element might be off the viewarea and hence the modal might cover the whole window
        window.scrollTo({
          top: 0,
          behavior: "smooth",
        });

        // need to wait for the above scrollto finish to get the right element's dimensions
        setTimeout(function () {
          // put the transcript inside the bottom modal content
          transcriptModalContent.append(transcriptWrapper);
          sideCloseButton.remove();

          const videoEl = document.querySelector(
            ".field--name-field-talk-video"
          );
          const videoEleDimensions = videoEl.getBoundingClientRect();
          let modalTop = window.innerHeight - videoEleDimensions.bottom - 5;

          //for devices, on landscape mode, the top might be < 0 so make the modal half the size:
          if (modalTop < 0) {
            modalTop *= -1 / 2;
            window.scrollTo({
              top: videoEleDimensions.top,
              behavior: "smooth",
            });
          }
          transcriptContent.style.height = `${modalTop}px`;
          transcriptWrapper.style.marginTop = 0; //reset the margin top set on the side section

          //show bottom modal panel
          transcriptModal.style.display = "block";
          transcriptWrapper.style.display = "block";

          let contentYPadding = 4; // this is the value from the CSS declaration: .transcript-modal-body {padding: 2px 16px}
          // FF does not support computedStyleMap() for now, so need to check:
          if ("computedStyleMap" in transcriptModalContent) {
            const modalContentPadding =
              transcriptModalContent.computedStyleMap() || null;
            if (modalContentPadding) {
              contentYPadding =
                modalContentPadding.get("padding-top").value +
                modalContentPadding.get("padding-bottom").value;
            }
          }

          //set the height of the content inside the modal:
          const calculatedModalContentHeight =
            transcriptContent.offsetHeight -
              transcriptModalHeader.offsetHeight -
              contentYPadding || 300;

          transcriptWrapper.style.height = `${calculatedModalContentHeight}px`;
        }, 300);
      } else {
        // hide the modal
        transcriptModal.style.display = "none";
      }
    } else {
      transcriptModal.style.display = "none";
      transcriptWrapper.style.display = "none";
    }
  }

  const toggleTranscript = document.getElementById("toggle_transcript");
  toggleTranscript.addEventListener("click", function (e) {
    isTranscriptShowing = !isTranscriptShowing;
    if (isTranscriptShowing) {
      if (window.innerWidth > cutoffWidth) {
        pageContentWrapper.classList.add(sideTranscriptWrapper);
      }
      toggleTranscript.firstElementChild.innerHTML = "Hide Transcript";
    } else {
      pageContentWrapper.classList.remove(sideTranscriptWrapper);
      toggleTranscript.firstElementChild.innerHTML = "Show Transcript";
    }

    toggleSideTranscript();
    toggleBottomTranscript();
  });

  //check if there's a video start time parameter in the url to play the video from that point:
  const search = window.location.search;
  const params = new URLSearchParams(search);
  let offset = search ? Number(params.get("t")) : false;

  const player = videojs("scitalk_video_js");
  const bts = transcriptWrapper.querySelectorAll(".jump_to");
  bts.forEach((itm) => {
    itm.addEventListener("click", function (e) {
      offset = this.value; //e.target.value;
      if (offset) {
        player.currentTime(offset);
        player.play();
      }
    });
  });

  // two ways of Highlighting the current text, either:
  //  1. use the "timeupdate" event on the player, or
  //  2. find the tracks and listen for the "cuechange" event
  // option 1 highlights faster than what the video is showing. It's better when another lang is selected under CC. Here we need to find the element with id = timestamp
  // option 2 highlights the text on time, it's more accurate. Here have to search for text on the arial-label to match the text.
  //    BUT!!!
  //      - it only works when the captions are on so I have to force the caption so at least "hidden"
  //      - Safari, Opera show the text in the aria-label escaped and to find the text on these cases i have to look inside the span

  let prevHighlightedText = null;

  /////////////////////

  // Option 1: listen for the timeupdate event from the video player

  player.on("timeupdate", (event) => {
    const curTime = parseInt(player.currentTime());
    if (curTime == 0) {
      return;
    }
    const elId = `${curTime}`;
    const el = document.getElementById(elId);
    //remove highlights from previous text:
    if (el) {
      if (prevHighlightedText) {
        prevHighlightedText.classList.toggle("highlighted_text");
      }

      const textBlock = el.nextElementSibling;
      prevHighlightedText = textBlock.firstChild;

      //this scolls inside the subtitles element to the current text
      transcriptWrapper.scrollTo({
        top: el.offsetTop - transcriptWrapper.offsetTop, //scroll to the play button above the highlighted text
        // top: sib.offsetTop - wrap.offsetTop, //scroll to the highlighted text
        behavior: "smooth",
      });
      textBlock.firstChild.classList.toggle("highlighted_text");
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
  //         prevHighlightedText.classList.toggle("highlighted_text");
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
  //       curText.classList.toggle("highlighted_text");
  //     });
  //   }
  // });

  /////////////////

  // on window resize determine which transcript section should appear (side or bottom)
  let resizeTimer;
  function updateTranscriptSection() {
    if (isTranscriptShowing) {
      //hide the side Transcript section when less than this screen width, else show
      if (window.innerWidth <= cutoffWidth) {
        pageContentWrapper.classList.remove(sideTranscriptWrapper);
      } else {
        pageContentWrapper.classList.add(sideTranscriptWrapper);
      }
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        toggleSideTranscript();
        toggleBottomTranscript();
      }, 200);
    }
  }

  window.addEventListener("resize", updateTranscriptSection);
  // screen.orientation.addEventListener("change", (event) => {});
});
