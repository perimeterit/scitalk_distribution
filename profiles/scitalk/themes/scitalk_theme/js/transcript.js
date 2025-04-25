document.addEventListener("DOMContentLoaded", () => {
  const transcriptModal = document.querySelector(".transcript-modal");
  const transcriptModalBtn = document.querySelector(".transcript-modal-close");
  const pageContentWrapper = document.querySelector(".content-wrapper");
  const sideTranscriptWrapper = "video-trans-wrapper";
  const transcriptWrapper = document.getElementById(
    "formatted-transcript-text"
  );

  const cutoffWidth = 1100; // width to determine whether to display left or bottom transcript section
  let isTranscriptShowing = false;

  //close Transcript modal
  transcriptModalBtn.addEventListener("click", () => {
    transcriptModal.style.display = "none";
    toggleTranscript.click();
  });

  // toggle the side Transcript section
  function toggleSideTranscript() {
    // side transcript section showing - add transcript:
    if (pageContentWrapper.classList.contains(sideTranscriptWrapper)) {
      // if big screen:
      if (window.innerWidth > cutoffWidth) {
        pageContentWrapper.append(transcriptWrapper);

        const videoEl = document.querySelector(".field--name-field-talk-video");
        const talkNumber = document.querySelector(".talk-number");
        const videoHeight = videoEl.offsetHeight;
        const videoOffset = Math.abs(
          talkNumber.offsetTop - talkNumber.offsetHeight
        );

        transcriptWrapper.style.marginTop = `${videoOffset}px`;
        transcriptWrapper.style.height = `${videoHeight}px`;
        transcriptWrapper.style.display = "block";
      }
    } else {
      transcriptWrapper.style.display = "none";
    }
  }

  // toggle bottom Transcript section
  function toggleBottomTranscript() {
    const transcriptContent = document.querySelector(
      ".transcript-modal-content"
    );
    const transcriptModalHeader = document.querySelector(
      ".transcript-modal-header"
    );
    const transcriptModalContent = document.querySelector(
      ".transcript-modal-body"
    );
    const transcriptWrapper = document.getElementById(
      "formatted-transcript-text"
    );

    if (isTranscriptShowing) {
      if (window.innerWidth <= cutoffWidth) {
        // put the transcript inside the bottom modal content
        transcriptModalContent.append(transcriptWrapper);

        const videoEl = document.querySelector(".field--name-field-talk-video");
        const videoEleDimensions = videoEl.getBoundingClientRect();
        const modalTop = window.innerHeight - videoEleDimensions.bottom - 5;
        transcriptContent.style.height = `${modalTop}px`;

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
        transcriptWrapper.style.marginTop = 0; //reset the margin top set on the side section
        transcriptWrapper.style.height = `${calculatedModalContentHeight}px`;
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

  // player.on("play", () => {
  //   if (offset) {
  //     player.currentTime(offset);
  //   }
  //   offset = false;
  // });

  let prevHighlightedText = null;
  // get time updates from the video player
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
});
