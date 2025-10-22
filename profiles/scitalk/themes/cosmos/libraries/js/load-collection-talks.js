(function ($) {
  Drupal.behaviors.scitalkLoadChildren = {
    attach: function (context, settings) {
      const toggleElements = once(
        "scitalkLoadChildren",
        ".collection .toggle-children",
        context
      );
      $(toggleElements, context).click(function () {
        // There may be multiple items with the same id.
        var collection_id = $(this).data("collection-id");
        // If it's currently closed
        if ($(this).attr("aria-expanded") == "false") {
          // Expand this one
          $(this).attr("aria-expanded", "true");
          $(this).children("span").html("Hide");
          $("#collection-children--" + collection_id).attr(
            "aria-hidden",
            "false"
          );
        }
        // If it's currently open
        else {
          $(this).attr("aria-expanded", "false");
          $(this).children("span").html("Show");
          $("#collection-children--" + collection_id).attr(
            "aria-hidden",
            "true"
          );
        }
      });
    },
  };
})(jQuery);
