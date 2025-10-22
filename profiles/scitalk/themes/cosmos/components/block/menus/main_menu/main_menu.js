(($) => {
  Drupal.behaviors.mainMenu = {
    attach(context) {

      // Expose mobile sub menu on click, keypress, or tap
      function toggleDropdown(triggerEl) {
        if (triggerEl.attr("aria-expanded") === "false") {
          triggerEl.attr("aria-expanded", "true");
        } else {
          triggerEl.attr("aria-expanded", "false");
        }
        triggerEl.toggleClass("open");
      }

      // On click dropdown toggle
      $(once("dropdown-toggle-click", ".menu-dropdown-trigger", context)).on(
        "click",
        function () {
          toggleDropdown($(this));
        }
      );

      // Close menu on escape key
      $(document).on("keyup", function (e) {
        if (e.which === 27) {
          $(".menu-dropdown-trigger[aria-expanded=true]")
            .attr("aria-expanded", "false")
            .toggleClass("open");
        }
      });
    },
  };
})(jQuery);
