
(function ($) {
  /**
   * Implement sticky header
  */
  Drupal.behaviors.stickyHeader = {
    attach: function (context, settings) {
      $(".site-header").sticky({topSpacing:0});
    }
  };
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
      if ((drupalSettings.scitalk.adv_search_page == 'true') &&
        (context == document)) {
        show_filters($('.filter-toggle'));
      }

      function show_filters(link_el, context) {
        $('.search-exposed-filters').hide().removeClass('visually-hidden').slideDown();
        link_el.removeClass('show-filters').addClass('hide-filters');
        link_el.children('.text').html('hide filters');
      }
      function hide_filters(link_el, context) {
        $('.search-exposed-filters').slideUp();
        link_el.removeClass('hide-filters').addClass('show-filters')
        link_el.children('.text').html('filter');
      }
    }
  };
  /**
   * Search display toggle
  */
  Drupal.behaviors.toggleSearchDisplay = {
    attach: function (context, settings) {

      $('button.switch-display', context).click(function() {
        var target = $(this).data('target');
        $('.search-display').addClass('hidden');
        $('.search-display[data-display=' + target + ']').removeClass('hidden');
        $(this).parents('.adv-search-wrapper').attr('data-show-display', target);
      });

      // Add a wrapper that will persist when page is changed via ajax
      if ($('.adv-search-wrapper').length == 0) {
        $('.advanced-search-form').wrap('<div class="adv-search-wrapper">');
      }

      // Make sure the right display is open after using the pager
      var display_attr = $('.adv-search-wrapper').attr('data-show-display');
      if (typeof display_attr !== 'undefined' && display_attr !== false) {
        var target = $('.adv-search-wrapper').attr('data-show-display');
        $('.search-display').addClass('hidden');
        $('.search-display[data-display=' + target + ']').removeClass('hidden');
      }
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
