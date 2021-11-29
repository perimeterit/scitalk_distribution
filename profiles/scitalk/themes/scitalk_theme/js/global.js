
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

  /**
   * View display toggle
  */
  Drupal.behaviors.scitalk_toggleViewDisplay = {
    attach: function (context, settings) {

      $('button.switch-display', context).click(function() {
        var target = $(this).data('target');
        $('.view-display').addClass('hidden');
        $('.view-display[data-display=' + target + ']').removeClass('hidden');
        $(this).parents('.adv-view-wrapper').attr('data-show-display', target);
      });

      // Add a wrapper that will persist when page is changed via ajax
      if ($('.adv-view-wrapper').length == 0) {
        $('.advanced-view-display').wrap('<div class="adv-view-wrapper">');
      }

      // Make sure the right display is open after using the pager
      var display_attr = $('.adv-view-wrapper').attr('data-show-display');
      if (typeof display_attr !== 'undefined' && display_attr !== false) {
        var target = $('.adv-view-wrapper').attr('data-show-display');
        $('.view-display').addClass('hidden');
        $('.view-display[data-display=' + target + ']').removeClass('hidden');
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
