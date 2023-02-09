(function ($) {
  Drupal.behaviors.speakerAutofill = {
    attach: function (context, settings) {
      // When leaving the last name field pre-populate the display name field
      const displayName = $(".field--name-field-sp-display-name input");
      const firstName = $(".field--name-field-sp-first-name input");
      const lastName = $(".field--name-field-sp-last-name input");

      $(lastName, context).on("blur", function () {
        if (!displayName.val() || 0 === displayName.val().length) {
          $(displayName).val(firstName.val() + " " + lastName.val());
        }
      });
    },
  };
})(jQuery);
