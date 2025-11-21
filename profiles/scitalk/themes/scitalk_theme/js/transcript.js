document.addEventListener("DOMContentLoaded", () => {
  const showTranscriptText = "View Transcript";
  const hideTranscriptText = "Hide Transcript";
  let curSelectedLanguageIdx = 0; // keep the currently selected lang index value
  function addTranscriptHtml(transcript_text) {
    const languages = createTranscriptLanguagesSelect();

    const html = `
      <div class="transcript-text-wrapper">
          <a href="#" id="toggle_transcript" class="toggle_transcript">
            <span class="text">${showTranscriptText}</span>
          </a>
      </div>

      <div id="formatted-transcript-text" class="transcript">
          ${transcript_text}
      </div>

      <div id="formatted-transcript-modal" class="transcript-modal">
        <!-- Modal content for transcript on smaller screens -->
        <div class="transcript-modal-content resizable" draggable="false">
          <div class="transcript-modal-header ">
            <div class="resizer"></div>
            <span class="transcript-modal-close">&times;</span>
            <h2>Transcript</h2>
          </div>
          <div class="transcript-modal-body">
          </div>
        </div>
      </div>
    `;

    // prepend the Transcript link to the transcript field under the Resources section:
    const attachments_el = document.querySelector(
      ".field--name-field-talk-transcripts"
    );
    const list_item = document.createElement("li");
    list_item.id = "transcript-modal-trigger";
    list_item.classList.add("field__item"); //field__item is the class used in the Display view fences settings
    const item = attachments_el.prepend(list_item);
    list_item.innerHTML = html;

    // now insert the languages dropdown before the body
    document
      .querySelector(".transcript-modal-content")
      .insertBefore(
        languages,
        document.querySelector(".transcript-modal-body")
      );

    return html;
  }

  // create a dropdown with the available languages
  // we have 2 such dropdowns, passing the id for each one
  function createTranscriptLanguagesSelect(select_id = "language_selection") {
    const availLanguagesEl = document.querySelectorAll(".transcript-text");
    const langWrapper = document.createElement("div");
    langWrapper.className = "languages-wrapper";

    const languagesSelection = document.createElement("select");
    languagesSelection.id = select_id;
    languagesSelection.className = "language-selection";

    availLanguagesEl.forEach((lang) => {
      const option = document.createElement("option");
      option.value = lang.dataset.transcriptLang;
      option.textContent = lang.dataset.transcriptLang;
      languagesSelection.appendChild(option);
    });

    langWrapper.appendChild(languagesSelection);
    languagesSelection.selectedIndex = curSelectedLanguageIdx;
    languagesSelection.addEventListener("change", (e) => {
      const lang = e.target.value;
      curSelectedLanguageIdx = e.target.selectedIndex;
      const trans = document.querySelector(
        '[data-transcript-lang="' + lang + '"]'
      );
      const formatted = formatTranscript(trans.innerText);
      const transText = document.getElementById("formatted-transcript-text");
      transText.innerHTML = formatted;
      const selectionEls = document.querySelectorAll(".language-selection");
      selectionEls.forEach((el) => {
        if (el.id != e.target.id) {
          el.selectedIndex = e.target.selectedIndex;
          el.value = lang;
        }
      });
      syncPlayingTimes();
    });

    return langWrapper;
  }

  function formatTranscript(str) {
    const regex =
      /((\d\d:)?\d{2}:\d{2}\.\d{3})\s+-->\s+((\d\d:)?\d{2}:\d{2}\.\d{3})((?:(?!\d\d:).)*|$)/gms;

    let formattedTranscript = str;
    formattedTranscript = formattedTranscript.replace(/WEBVTT\s*/ms, "");
    while ((res = regex.exec(str)) !== null) {
      const start = res[1];
      const start_time = start.split(":");
      const text_block = res[5];
      const text_block_trimmed = text_block.trim();

      if (start_time.length === 2) {
        // mm:ss.milsec (not hr in the time)
        const min = start_time[0];
        const sec = start_time[1].split(".")[0];
        const min_to_secs = parseInt(min, 10) * 60 + parseInt(sec, 10);
        const display_time = `${min}:${sec}`;

        const link = `<div class="trans_wrap"><div class="jump_to_wrap" id="${min_to_secs}"><button class="jump_to" value="${min_to_secs}" aria-description="Start playing at interval ${display_time}"><div><i aria-hidden="true" class="icon-play"></i><span class="timestamp">${display_time}</span></div></button></div><div class="trans_text" aria-label="${text_block_trimmed}"><span>${text_block}</span></div></div>`;

        formattedTranscript = formattedTranscript.split(res[0]).join(link);
      } else if (start_time.length === 3) {
        // hh:mm:ss.milsec
        const hr = start_time[0];
        const min = start_time[1];
        const sec = start_time[2].split(".")[0];
        const hrs_to_secs =
          parseInt(hr, 10) * 3600 + parseInt(min, 10) * 60 + parseInt(sec, 10);
        const display_time =
          parseInt(hr, 10) > 0 ? `${hr}:${min}:${sec}` : `${min}:${sec}`;

        const link = `<div class="trans_wrap"><div class="jump_to_wrap" id="${hrs_to_secs}"><button class="jump_to" value="${hrs_to_secs}" aria-description="Start playing at interval ${display_time}"><div><i aria-hidden="true" class="icon-play"></i><span class="timestamp">${display_time}</span></div></button></div><div class="trans_text" aria-label="${text_block_trimmed}"><span>${text_block}</span></div></div>`;

        formattedTranscript = formattedTranscript.split(res[0]).join(link);
      }
    }
    return formattedTranscript;
  }

  const talkTranscripts = document.querySelectorAll(
    ".resource--scitalk_transcription"
  );

  // on page load check for the existance of transcript text and automatically load the first on the list:
  if (talkTranscripts.length) {
    const trans = talkTranscripts[0].querySelector(".transcript-text");
    const transText = formatTranscript(trans.innerText);
    addTranscriptHtml(transText);
  }

  const hasTranscript = document.querySelector(".transcript-text-wrapper");
  if (!hasTranscript) {
    return;
  }

  const transcriptModal = document.querySelector(".transcript-modal");
  // const transcriptModalBtn = document.querySelector(".transcript-modal-close");
  const pageContentWrapper = document.querySelector(".content-wrapper");
  const transcriptModalClose = document.querySelector(
    ".transcript-modal-close"
  );

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
  transcriptModalClose.addEventListener("click", () => {
    transcriptModal.style.display = "none";
    toggleTranscript.click();
  });

  // need to add a Close button for the side Transcript
  const sideCloseButton = (function () {
    let sideWrap = null;
    let languagesWrap = null;
    return {
      create: function () {
        if (!sideWrap) {
          sideWrap = document.createElement("div");
          sideWrap.id = "side-wrap";
          const cBox = document.createElement("div");
          cBox.className = "close-side";
          cBox.innerHTML = `<span class="close-side-btn">×</span><h2>${hideTranscriptText}</h2>`;
          sideWrap.append(cBox);

          languagesWrap = createTranscriptLanguagesSelect(
            "side_language_selection"
          );
          sideWrap.append(languagesWrap);

          // close the transcript
          cBox.addEventListener("click", () => {
            sideWrap.style.display = "none";
            transcriptModalClose.click();
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

        const talkNodeEl = document.querySelector(".node.talk"); // this the el where the node Talk is enclosed
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

    // calculate the height of the bottom modal header
    function getModalHeaderHeight() {
      const defaultHeight = 300;
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

      const calculatedModalHeaderHeight =
        transcriptModalHeader.offsetHeight - contentYPadding || defaultHeight;
      return calculatedModalHeaderHeight;
    }

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

          //set the height of the content inside the modal:
          const calculatedModalContentHeight =
            transcriptContent.offsetHeight - getModalHeaderHeight();

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

    /////////// resize bottom modal
    (function () {
      let modalH = 0;
      let modalYPos = 0;

      function updateResizeCursor() {
        transcriptContent.style.cursor = "row-resize";
        transcriptContent.style.userSelect = "none";
      }

      function resetResiseCursor() {
        transcriptContent.style.removeProperty("cursor");
        transcriptContent.style.removeProperty("user-select");
      }

      transcriptModalHeader.addEventListener("mousedown", (e) => {
        modalYPos = e.clientY;

        const styles = window.getComputedStyle(transcriptContent);
        modalH = parseInt(styles.height, 10);

        document.addEventListener("mousemove", mouseMoveHandler);
        document.addEventListener("mouseup", mouseUpHandler);

        function mouseMoveHandler(e) {
          // calculate how much the mouse moved on the vertical
          const dy = e.clientY - modalYPos;
          const newH = modalH + -1 * dy;
          const calculatedModalContentHeight = newH - getModalHeaderHeight();

          transcriptContent.style.height = `${newH}px`;
          transcriptWrapper.style.height = `${calculatedModalContentHeight}px`;
          updateResizeCursor();
        }

        function mouseUpHandler() {
          // Remove the handlers of mousemove and mouseup
          document.removeEventListener("mousemove", mouseMoveHandler);
          document.removeEventListener("mouseup", mouseUpHandler);
          resetResiseCursor();
        }
      });

      // transcriptModal.addEventListener("touchstart", (e) => {
      transcriptModalHeader.addEventListener("touchstart", (e) => {
        const touch = e.touches[0];
        modalYPos = touch.clientY;

        const styles = window.getComputedStyle(transcriptContent);
        modalH = parseInt(styles.height, 10);

        document.addEventListener("touchmove", handleTouchMove);
        document.addEventListener("touchend", handleTouchEnd);

        function handleTouchMove(e) {
          const touch = e.touches[0];
          const dy = touch.clientY - modalYPos;
          const newH = modalH + -1 * dy;
          const calculatedModalContentHeight = newH - getModalHeaderHeight();

          transcriptContent.style.height = `${newH}px`;
          transcriptWrapper.style.height = `${calculatedModalContentHeight}px`;
          updateResizeCursor();
        }

        function handleTouchEnd() {
          // Remove the handlers of mousemove and mouseup
          document.removeEventListener("touchmove", handleTouchMove);
          document.removeEventListener("touchend", handleTouchEnd);
          resetResiseCursor();
        }
      });
    })();
  }

  const toggleTranscript = document.getElementById("toggle_transcript");
  toggleTranscript.addEventListener("click", function (e) {
    isTranscriptShowing = !isTranscriptShowing;
    if (isTranscriptShowing) {
      if (window.innerWidth > cutoffWidth) {
        pageContentWrapper.classList.add(sideTranscriptWrapper);
      }
      toggleTranscript.firstElementChild.innerHTML = hideTranscriptText;
    } else {
      pageContentWrapper.classList.remove(sideTranscriptWrapper);
      toggleTranscript.firstElementChild.innerHTML = showTranscriptText;
    }

    toggleSideTranscript();
    toggleBottomTranscript();
  });

  //check if there's a video start time parameter in the url to play the video from that point:
  const search = window.location.search;
  const params = new URLSearchParams(search);
  let offset = search ? Number(params.get("t")) : false;

  const player = videojs("scitalk_video_js");

  // sync times between player and transcript text so that we could skip to a specific
  // time on the video when clicking on a time stamp in the transcript:
  function syncPlayingTimes() {
    const jumpToBtns = transcriptWrapper.querySelectorAll(".jump_to");
    jumpToBtns.forEach((itm) => {
      itm.addEventListener("click", function (e) {
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
  })();

  /////////////////

  // on window resize determine which transcript section should appear (side or bottom)
  (function () {
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
  })();
});
