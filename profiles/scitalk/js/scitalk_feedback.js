(function ($) {
  Drupal.behaviors.scitalk = {
    attach: function (context, settings) {
      'use strict';
      //fill the page/video field with referer url:
       $('#edit-field-feedback-page-0-value', context).val(settings.scitalk.scitalk_profile_js.feedback_referer);
    }
  };
})(jQuery);