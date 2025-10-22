(($) => {
  Drupal.behaviors.showSearch = {
    attach(context) {

      const searchToggle = $(
        once("search-toggle", ".toggle-search", context)
      );
      const search_container = $("#search-form-wrapper", context);

      function closeSearch() {
        searchToggle.attr("aria-expanded", "false");
        search_container.removeClass("search-open");
      }

      searchToggle.on("click", function (e) {
        if (searchToggle.attr("aria-expanded") === "false") {
          searchToggle.attr("aria-expanded", "true");
          search_container.addClass("search-open");
        } else {
          closeSearch();
        }
      });

      // Revert search form on esc
      $(document).on("keyup", function (e) {
        if (e.which === 27) {
          if (search_container.hasClass('search-open')) {
            closeSearch();
          }
        }
      });
    },
  };
})(jQuery);
