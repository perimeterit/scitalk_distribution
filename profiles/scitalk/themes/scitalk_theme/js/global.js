
(function ($) {
/**
 * Responsive tables for Latest Talks table display
*/
  Drupal.behaviors.responsiveTable = {
    attach: function (context, settings) {
      $.responsiveTables('800px');
    }
  };

  // Toggle advanced search filters
  Drupal.behaviors.toggleAdvanced = {
    attach: function (context, settings) {
      // Toggle on click
      $('.filter-toggle', context).click(function() {
        if ($(this).hasClass('show-filters')) {
          show_filters($(this));
        }
        else if ($(this).hasClass('hide-filters')) {
          hide_filters($(this));
        }
      });

      // Open by default on Search landing page
      console.log(drupalSettings.scitalk);
      if (drupalSettings.scitalk.adv_search_page == 'true') {
        show_filters($('.filter-toggle'));
      }

      function show_filters(link_el) {
        $('.search-exposed-filters').hide().removeClass('visually-hidden').slideDown();
        link_el.removeClass('show-filters').addClass('hide-filters');
        link_el.children('.text').html('hide filters');
      }
      function hide_filters(link_el) {
        $('.search-exposed-filters').slideUp();
        link_el.removeClass('hide-filters').addClass('show-filters')
        link_el.children('.text').html('filter');
      }
    }
  };
  /**
   * Related talks
  */
  Drupal.behaviors.toggleSearchDisplay = {
    attach: function (context, settings) {
      $('button.switch-display', context).click(function() {
        $target = $(this).data('target');
        $('.search-display').addClass('hidden');
        $('.search-display[data-display=' + $target + ']').removeClass('hidden');
      })

    }
  };
  /**
   * Related talks
  */
  Drupal.behaviors.toggleRelatedPosts = {
    attach: function (context, settings) {

      if (typeof(context.querySelector) === 'function') {
        let relatedPosts = context.querySelector('.related-talks-view');
        if (relatedPosts) {
          context.querySelector('h2.related-talks-toggle')
          .addEventListener('click', event => {
            relatedPosts.classList.toggle('show');
          });
        }
      }
    }
  };
}(jQuery));
