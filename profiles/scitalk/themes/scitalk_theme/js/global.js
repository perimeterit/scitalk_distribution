
/**
 * Responsive tables for Latest Talks table display
*/
(function ($) {
  Drupal.behaviors.responsiveTable = {
    attach: function (context, settings) {
      $.responsiveTables('800px');
    }
  };
})(jQuery);

/**
 * Related talks
*/
(function (Drupal) {
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
})(Drupal);

/**
 * Advanced search
*/
(function ($) {
  Drupal.behaviors.toggleAdvanced = {
    attach: function (context, settings) {
      const adv_btn = '<span class="toggle-advanced_wrapper"><a class="toggle-advanced" href="#" title="Toggle Advanced Search">Advanced</a></span>';
      $('.advanced-search-wrapper #edit-submit-default-search-content', context).parent().append(adv_btn);
      
      $('.toggle-advanced', context).click( function(e){
        e.preventDefault(); 
        $('.block-views-exposed-filter-block--scitalk-default-advanced-search-advanced-search').toggle();
      });

    }
  };
})(jQuery);