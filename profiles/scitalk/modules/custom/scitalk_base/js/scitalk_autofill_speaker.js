(function ($) {
  Drupal.behaviors.speakerAutofill = {
    attach: function (context, settings) {
      // When leaving the last name field pre-populate the display name field
      const titleField = $(".field--name-title input");
      const firstName = $(".field--name-field-sp-first-name input");
      const lastName = $(".field--name-field-sp-last-name input");

      $(lastName, context).on("blur", function () {
        if (!titleField.val() || 0 === titleField.val().length) {
          $(titleField).val(firstName.val() + " " + lastName.val());
        }
      });
    },
  };
})(jQuery);
