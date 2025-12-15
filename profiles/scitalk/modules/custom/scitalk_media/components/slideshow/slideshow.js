document.addEventListener("DOMContentLoaded", () => {
  const slider = tns({
    container: ".talk-slider",
    loop: true,
    items: 1,
    slideBy: "page",
    nav: false,
    // autoplay: true,
    speed: 400,
    autoplayButtonOutput: false,
    mouseDrag: true,
    lazyload: true,
    loop: false,
    controlsContainer: "#customize-controls",
    responsive: {
      640: {
        items: 2,
      },
      768: {
        items: 3,
      },
    },
    onInit: function (info) {
      if (info.index === 0) {
        toggleSliderNavButtons(info);
      }
    },
  });

  slider.events.on("indexChanged", function (info) {
    toggleSliderNavButtons(info);
  });

  // disable left nav if on first page, right nav if on last page of the Slider
  function toggleSliderNavButtons(info) {
    const navControls = document.getElementById("customize-controls");

    if (info.index + info.slideBy >= info.slideCount) {
      navControls.querySelector(".next i").classList.add("slide-nav-disabled");
    } else if (info.index === 0) {
      navControls.querySelector(".prev i").classList.add("slide-nav-disabled");
    } else {
      navControls.querySelectorAll("i").forEach((e) => {
        e.classList.remove("slide-nav-disabled");
      });
    }
  }

  const cards = document.querySelectorAll(".talk-slider-card img");

  const slideModal = (function () {
    const modal = document.getElementById("slides-modal");
    const sliderContainer = document.querySelector(".talk-slider-container");
    const modalImg = document.getElementById("slides-modal-content");
    const captionText = document.getElementById("slides-modal-caption");
    const close = document.querySelector("#slides-modal .slides-modal-close");
    const modalPrev = document.querySelector(".slides-modal-body .prev");
    const modalNext = document.querySelector(".slides-modal-body .next");

    const info = slider.getInfo();
    let slideBy = info.slideBy;
    const slideCount = info.slideCount;
    let curCardIdx = 0;

    //close modal
    close.addEventListener("click", (e) => {
      modal.style.display = "none";
      slider.slideBy = slideBy; //reset the orig slideBy setting
      sliderContainer.style.opacity = 1;
    });

    // display modal
    function showModal(card_el) {
      modal.style.display = "flex";
      modalImg.src = card_el.target.src;
      captionText.innerHTML = "<h3>" + card_el.target.alt + "</h3>";
      curCardIdx = getCurModalImageIdx(card_el.target);
      placeModal();
      toggleModalNavButtons();
    }

    function isModalVisible() {
      return modal.style.display == "flex";
    }

    // set the modal with to that of the slider
    function placeModal() {
      const sliderRect = sliderContainer.getBoundingClientRect();
      const width = sliderRect.width + "px";
      modal.style.maxWidth = width;
      sliderContainer.style.opacity = 0.1;
    }

    // disable left nav if on first page, right nav if on last page of the modal
    function toggleModalNavButtons() {
      if (curCardIdx >= slideCount - 1) {
        modalNext.querySelector("i").classList.add("slide-nav-disabled");
      } else if (curCardIdx < 1) {
        modalPrev.querySelector("i").classList.add("slide-nav-disabled");
      } else {
        document
          .getElementById("customize-modal-controls")
          .querySelectorAll("i")
          .forEach((e) => {
            e.classList.remove("slide-nav-disabled");
          });
      }
    }

    // check if el is inside a slider card
    function isCard(el) {
      return el?.parentNode?.classList.contains("talk-slider-card");
    }

    window.addEventListener("resize", () => {
      // when the screen gets small enough to change the number of slider displayed, need to make sure
      // that if we were on the last page, the next nav becomes enabled again in case another page gets added:
      const info = slider.getInfo();
      const curSlideBy = info.slideBy;
      if (slideBy != curSlideBy) {
        slideBy = curSlideBy;
        toggleSliderNavButtons(info);
      }

      if (isModalVisible()) {
        placeModal();
      }
    });

    // addEventListener("scroll", () => {
    //   // keep it in place when scrolling
    //   if (modal.style.display == "flex") {
    //     placeModal();
    //   }
    // });

    // find the idx of the currently selected slider item
    function getCurModalImageIdx(card_el) {
      return [...cards].findIndex((c) => c == card_el);
    }

    // move to the next slider card and set the card on the modal
    function moveToNextSlideInModal() {
      slider.slideBy = 1; // set the slideBy setting to 1 when navigating thru the modal
      slider.goTo(curCardIdx); // move to the next item in the slide by 1 (ie. slideBy)
      const curCard = cards[curCardIdx];
      toggleModalNavButtons();
      if (curCard) {
        curCard.click();
      }
    }

    // modal card prev navigation
    modalPrev.addEventListener("click", () => {
      curCardIdx = curCardIdx < 1 ? curCardIdx : curCardIdx - 1;
      moveToNextSlideInModal();
    });

    // modal card next navigation
    modalNext.addEventListener("click", () => {
      curCardIdx = curCardIdx >= slideCount - 1 ? curCardIdx : curCardIdx + 1;
      moveToNextSlideInModal();
    });

    // close the modal when clicking outside it
    document.addEventListener("click", (e) => {
      const el = e.target;
      if (!isCard(el) && !modal.contains(el)) {
        close.click();
      }
    });

    return {
      show: function (card_el) {
        showModal(card_el);
      },
    };
  })();

  cards.forEach((card) => {
    card.addEventListener("click", (e) => {
      slideModal.show(e);
    });
  });
});
