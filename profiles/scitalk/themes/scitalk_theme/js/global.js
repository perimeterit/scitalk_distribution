
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

      $('.filter-toggle', context).click(function() {
        if ($(this).hasClass('show-filters')) {
          $('.search-exposed-filters').hide().removeClass('visually-hidden').slideDown();
          $(this).removeClass('show-filters').addClass('hide-filters');
          $(this).children('.text').html('hide filters');
        }
        else if ($(this).hasClass('hide-filters')) {
          $('.search-exposed-filters').slideUp();
          $(this).removeClass('hide-filters').addClass('show-filters')
          $(this).children('.text').html('filter');
        }

      });
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
