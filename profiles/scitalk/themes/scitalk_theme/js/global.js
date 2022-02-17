
(function ($, Drupal, drupalSettings) {
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
  Drupal.behaviors.scitalk_toggleAdvancedSearch = {
    attach: function (context, settings) {
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
   * Make sure type details element is always collapsed
   * By default BEF will make this open by default if there is a selection
  */
   Drupal.behaviors.scitalk_fixDetailsFilter = {
    attach: function (context, settings) {
      $('.advanced-view-header details[open]').removeAttr('open');
    }
  };


  /**
   * Teaser hide/show toggle
  */
  Drupal.behaviors.toggleAbstract = {
    attach: function (context, settings) {
      // Remove any paragraphs contianing only a nbsp
      $('.field--name-field-talk-abstract p').filter(function(){
         return this.innerHTML == '&nbsp;';
      }).remove();

      // Toggle the full content
      $('.node.teaser .show-more', context).click(function() {
        var show_text = $(this).html();
        if ($(this).hasClass('show')) {
          $(this).html($(this).attr('data-hide-text'));
        }
        if ($(this).hasClass('hide')) {
          $(this).html($(this).attr('data-show-text'));
        }
        $(this).parents('.node-content').toggleClass('collapse-abstract').toggleClass('show-abstract');
        $(this).toggleClass('show').toggleClass('hide');

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

  // Override Core Ajax scroll to top behaviour
  // Adjusts scroll position to accommodate sticky header
  Drupal.AjaxCommands.prototype.viewsScrollTop = function (ajax, response) {
    // Scroll to the top of the view. This will allow users
    // to browse newly loaded content after e.g. clicking a pager
    // link.
    var offset = $(response.selector).offset();

    // We can't guarantee that the scrollable object should be
    // the body, as the view could be embedded in something
    // more complex such as a modal popup. Recurse up the DOM
    // and scroll the first element that has a non-zero top.
    var scrollTarget = response.selector;
    while ($(scrollTarget).scrollTop() === 0 && $(scrollTarget).parent()) {
      scrollTarget = $(scrollTarget).parent();
    }
    console.log(scrollTarget);


    // Only scroll upward.
    if (offset.top - 10 < $(scrollTarget).scrollTop()) {
      $(scrollTarget).animate({ scrollTop: offset.top - 110 }, 500);
    }
  };

})(jQuery, Drupal, drupalSettings);
