(function(Drupal, $) {

  /**
   * View display toggle
   */
  Drupal.behaviors.cosmos_toggleViewDisplay = {
    attach: function (context, settings) {
      $("button.switch-display", context).click(function () {
        $("button.switch-display.active").removeClass("active");
        var target = $(this).data("target");
        $(".view-display").addClass("hidden");
        $(".view-display[data-display=" + target + "]").removeClass("hidden");
        $(this).parents(".adv-view-wrapper").attr("data-show-display", target);
        $(this).addClass("active");
      });

      // Make sure the right display is open after using the pager
      var display_attr = $(".adv-view-wrapper").attr("data-show-display");
      if (typeof display_attr !== "undefined" && display_attr !== false) {
        var target = $(".adv-view-wrapper").attr("data-show-display");
        $(".view-display").addClass("hidden");
        $(".view-display[data-display=" + target + "]").removeClass("hidden");

        // And that the right button is highlighted
        $("button.switch-display.active").removeClass("active");
        $('button.switch-display[data-target="' + target + '"]').addClass(
          "active"
        );
      }

    },
  };

})(Drupal, jQuery);
