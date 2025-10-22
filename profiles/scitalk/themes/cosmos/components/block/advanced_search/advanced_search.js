(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.scitalk_toggleAdvancedSearch = {
    attach: function (context, settings) {

    // Open the correct search form when the select form changes
      $("#search-type-select", context).on("change", function () {
        $(".advanced-search-form").addClass("closed");
        $(".search-form--" + this.value).removeClass("closed");
      });

      // On Source Repo pages, pre-populate Source field in the search form
      if (typeof drupalSettings.cosmos.group_name != "undefined") {
        var group_val = drupalSettings.cosmos.group_name[0]["value"];
        $(
          "#views-exposed-form-scitalk-advanced-search-form-block #edit-source",
          "#views-exposed-form-scitalk-search-collections-form-block #edit-collection-source"
        ).val(group_val);
      }
    },
  };
})(jQuery, Drupal, drupalSettings);
