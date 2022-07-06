
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.scitalk_toggleAdvancedSearch = {
    attach: function (context, settings) {


      // Open advanced search form
      $('.open-advanced-search').click(function() {
        $('.region-left-nav .menu--main').addClass('hide');
        $('body').delay(100).addClass('adv-search-open');
        $('.open-advanced-search, .close-advanced-search').attr('aria-expanded','true');
      })

      $('.close-advanced-search').click(function() {
        $('body').removeClass('adv-search-open');
        $('.open-advanced-search, .close-advanced-search').attr('aria-expanded','false');
        $('.region-left-nav .menu--main').removeClass('hide');
      })

      // Open by default on Search landing page
      if ((drupalSettings.scitalk.is_search_page == 'true') &&
        (context == document)) {
        $('.open-advanced-search').trigger('click');
      }

      // Open the right search section when the select form changes
      $('#search-type-select', context).on('change', function() {
          $('.search-form').addClass('closed');
          $('.search-form--' + this.value).removeClass('closed');
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
