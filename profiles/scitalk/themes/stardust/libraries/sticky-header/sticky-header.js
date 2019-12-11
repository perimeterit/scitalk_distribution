(function ($) {
 // Store our function as a property of Drupal.behaviors.
 Drupal.behaviors.starlight_sticky_header = {
    attach: function (context, settings) {

      $('header.site-header').sticky();
      if ($('#toolbar-bar').length > 0) {
        var toolbarHeight = $('#toolbar-bar').height();
        $('header.site-header').on('sticky-start', function() {
          $(this).css('margin-top',toolbarHeight);
        });
        $('header.site-header').on('sticky-end', function() {
          $(this).css('margin-top',0);
        });
      }

  }
 }
}(jQuery));
