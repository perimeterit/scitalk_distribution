
(function ($) {
/**
 * Responsive tables for Latest Talks table display
*/
  Drupal.behaviors.responsiveTable = {
    attach: function (context, settings) {
      $.responsiveTables('800px');
    }
  };

  Drupal.behaviors.toggleAdvanced = {
    attach: function (context, settings) {
      $adv_btn = '<a class="toggle-advanced toggle-expand" href="#" title="Toggle Advanced Search">Show advanced</a>';
      $adv_search_el = $('.block-views-exposed-filter-block--scitalk-advanced-search-advanced-search');
      $basic_search_el = $('#views-exposed-form-scitalk-advanced-search-basic-search');

      $adv_search_el.hide();
      $('#views-exposed-form-scitalk-advanced-search-basic-search .form-actions', context).append($adv_btn);
      $('.search-wrap', context).prepend('<span class="hide-advanced toggle-expand toggle-contract">Hide advanced</span>');

      $('.toggle-advanced', context).click( function(e){
        e.preventDefault();

        $adv_search_el.slideDown();
        $basic_search_el.slideUp();

        // Prepopulate search & subjects fields with values from simple search bar
        $form_search_val = $('.form-item-search .form-text', $basic_search_el).val();
        $form_search_subject = $('.form-item-talk-subject .form-select', $basic_search_el).val();
        $('.form-item-search .form-text', $adv_search_el).val($form_search_val);
        $('.form-item-talk-subject .form-select', $adv_search_el).val($form_search_subject);
      });

      $('.hide-advanced', context).click(function(){
        $adv_search_el.slideUp();
        $basic_search_el.slideDown();
      });

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
