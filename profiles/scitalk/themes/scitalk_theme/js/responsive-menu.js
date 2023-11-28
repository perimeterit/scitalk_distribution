(function ($) {
  // Store our function as a property of Drupal.behaviors.
  Drupal.behaviors.scitalk_mobile_menus = {
    attach: function (context, settings) {
      // Add the close button to the left nav wrapper.
      $(once("add-menu-close", ".left-nav-wrapper", context)).prepend(
        '<button class="menu--close menu--icon" aria-expanded="true"><span class="visually-hidden">' +
          $(".menu-toggle span").html() +
          "</span></button>"
      );
      // Toggle the menu open/closed, from the hamburger icon or the close
      $menu_toggle_buttons = $(".menu-toggle, .menu--close", context);
      $($menu_toggle_buttons).on("click", function () {
        console.log("click");
        if ($(this).attr("aria-expanded") == "false") {
          // Open the menu
          $menu_toggle_buttons.attr("aria-expanded", "true");
          // Also moves the focus to the first link item (for screen readers)
          $("#menu-container .menu--main").attr("tabindex", "-1").focus();
          // Change the button text
          $toggle_text = $(".menu-toggle span").html();
          $(this)
            .children("span")
            .html($(".menu-toggle").attr("data-hide-text"));
        } else {
          // Close the menu
          $menu_toggle_buttons.attr("aria-expanded", "false");
          $(".menu-toggle").focus();
          $menu_toggle_buttons.children("span").html($toggle_text);
        }
        $("body", context).toggleClass("menu--open");
      });
    },
  };

  // / Search toggle
  Drupal.behaviors.scitalk_search_toggle = {
    attach: function (context, settings) {
      $(".search-toggle", context).click(function () {
        if ($(this).attr("aria-expanded") == "false") {
          $(this).attr("aria-expanded", "true");
          // Also moves the focus to the first link item (for screen readers)
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
})(jQuery);
