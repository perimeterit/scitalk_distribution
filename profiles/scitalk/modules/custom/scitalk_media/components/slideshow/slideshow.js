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
  });

  const slideModal = (function () {
    const modal = document.getElementById("slides-modal");
    const modalImg = document.getElementById("slide-content");
    const captionText = document.getElementById("slide-caption");
    const close = document.querySelector("#slides-modal .slide-close");
    close.addEventListener("click", (e) => {
      modal.style.display = "none";
    });

    function isCard(el) {
      return el?.parentNode?.classList.contains("card");
    }

    // close the modal when clicking outside it
    document.addEventListener("click", (e) => {
      const el = e.target;
      if (!isCard(el) && !modal.contains(el)) {
        close.click();
      }
    });

    return {
      show: function (card_el) {
        modal.style.display = "block";
        modalImg.src = card_el.target.src;
        captionText.innerHTML = card_el.target.alt;
      },
    };
  })();

  const cards = document.querySelectorAll(".card img");
  cards.forEach((card) => {
    card.addEventListener("click", (e) => {
      slideModal.show(e);
    });
  });
});
