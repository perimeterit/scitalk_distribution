(function ($, Drupal, once) {
  // Basic search (top header) toggle
  Drupal.behaviors.cosmosSearchToggle = {
    attach: function (context, settings) {
      $(once("search-toggle", ".search-toggle", context)).click(function () {
        if ($(this).attr("aria-expanded") == "false") {
          $(this).attr("aria-expanded", "true");
          // Also moves the focus to the input field (for screen readers)
          $(".search-block-form")
            .attr("tabindex", "-1")
            .toggleClass("open")
            .focus();
          // Change the button text
          $toggle_text = $(this).children("span").html();
          $(this).children("span").html($(this).attr("data-hide-text"));
        } else {
          // Close the search form
          $(this)
            .attr("aria-expanded", "false")
            .children("span")
            .html($toggle_text)
            .focus();
          $(".search-block-form").toggleClass("open");
        }
      });
    },
  };

  Drupal.behaviors.responsiveMenu = {
    attach(context) {
      // Toggle the menu open/closed, from the hamburger icon or the close
      $menu_toggle_buttons = $(".menu-toggle, .menu--close").each(function () {
        $(once("mobile-toggle", $(this), context)).on("click", function () {
          console.log("mobile-toggle");
          if ($(this).attr("aria-expanded") == "false") {
            // Open the menu
            $menu_toggle_buttons.attr("aria-expanded", "true");
            // Also moves the focus to the first link item (for screen readers)
            $("#main-nav .menu--main").attr("tabindex", "-1").focus();
            // Change the button text
            $toggle_text = $(".menu-toggle span").html();
            $(this)
              .children("span")
              .html($(".menu-toggle").attr("data-hide-text"));
              $('#main-nav').addClass('open');
          } else {
            // Close the menu
            $menu_toggle_buttons.attr("aria-expanded", "false");
            $(".menu-toggle").focus();
            $menu_toggle_buttons.children("span").html($toggle_text);
            $("#main-nav").removeClass("open");
          }
          $("body", context).toggleClass("menu--open");
        });
      });
    },
  };

  // Advanced search (sidebar) toggle
  Drupal.behaviors.cosmosAdvancedSearch = {
    attach(context) {
      // Open advanced search form
      $(
        once("advanced-search-toggle", ".advanced-search-toggle", context)
      ).click(function () {
        // Add classes to show this is open now
        $(".main-nav").addClass("adv-search-open");
        $(".page-layout").delay(100).addClass("adv-search-open");

        // Change the aria-expanded label
        $(".advanced-search-toggle, .close-advanced-search-toggle").attr(
          "aria-expanded",
          "true"
        );
      });

      $(
        once("close-advanced-search", ".close-advanced-search-toggle", context)
      ).click(function () {
        // Animate the height of the wrapper to this height
        $(".main-nav").removeClass("adv-search-open");
        $(".page-layout").delay(100).removeClass("adv-search-open");

        // Change the aria-expanded label
        $(".open-advanced-search-toggle, .close-advanced-search-toggle").attr(
          "aria-expanded",
          "false"
        );
      });

      // Open by default on Search landing page
      if (
        drupalSettings.cosmos.is_search_page == "true" &&
        context == document
      ) {
        $(".open-advanced-search").trigger("click");
      }
    },
  };
})(jQuery, Drupal, once);
