(function ($, Drupal) {
  Drupal.behaviors.cosmosPopover = {
    attach: function (context, settings) {

      // Make popover appear on hover, using hoverintent
      var popover_button = once(
        "popover",
        ".popover",
        context,
      );

      function popover_show(popoverEl) {
        $(popoverEl)
          .addClass("popover-show")
          .siblings(".popover-content")
          .addClass("popover-show");
      }

      function popover_hide(popoverEl) {
        $(popoverEl)
          .removeClass("popover-show")
          .siblings(".popover-content")
          .removeClass("popover-show");
      }

      // set actions for mouse over and mouse leave
      new SV.HoverIntent(popover_button, {
        // required parameters
        onEnter: function(targetItem) {
          popover_show(targetItem);
        },
        onExit: function(targetItem) {
          popover_hide(targetItem);
        },

        // default options
        exitDelay: 400,
        interval: 100,
        sensitivity: 7,
      });

      // Show on click
      $(popover_button).on("click", function (e) {
        popover_show($(this));

        // Hide on click again (probably mainly mobile)
        $(this).on("click", function () {
          popover_hide($(this));
        });
      });


      // Hide on escape key press.
      $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
          popover_hide($(popover_button));
        }
      });

    }
  };
})(jQuery, Drupal);
