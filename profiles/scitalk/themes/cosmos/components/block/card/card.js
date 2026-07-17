  Drupal.behaviors.toggleAbstract = {
    attach: function (context) {
      var root = context || document;

      // Remove any paragraphs containing only a nbsp.
      root
        .querySelectorAll(".field--name-field-talk-abstract p")
        .forEach(function (paragraph) {
          if (paragraph.innerHTML === "&nbsp;") {
            paragraph.remove();
          }
        });

      // Toggle the full content.
      root.querySelectorAll(".card--teaser .show-more").forEach(function (toggle) {

        toggle.addEventListener("click", function () {
          toggle.classList.toggle("show");
          toggle.classList.toggle("hide");

          if (toggle.classList.contains("hide")) {
            toggle.innerHTML = toggle.getAttribute("data-hide-text");
          }

          if (toggle.classList.contains("show")) {
            toggle.innerHTML = toggle.getAttribute("data-show-text");
          }

          var cardContent = toggle.closest(".card-content");
        });
      });
    },
  };
