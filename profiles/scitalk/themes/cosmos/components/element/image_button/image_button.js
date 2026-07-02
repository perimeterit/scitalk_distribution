(function (Drupal, drupalSettings) {
  Drupal.behaviors.cosmos_imageButtonCheckbox = {
    attach: function (context, settings) {

      // Add event listener to checkboxes, if it's enter, then trigger a click
      const checkboxes = context.querySelectorAll('.image-button--checkbox');
      checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('keydown', (event) => {
          if (event.key === 'Enter') {
            event.preventDefault();
            event.target.click();
          }
        });
      });

    },
  };
})( Drupal, drupalSettings);
