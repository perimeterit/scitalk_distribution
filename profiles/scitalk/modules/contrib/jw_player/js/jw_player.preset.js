(function ($) {

  Drupal.behaviors.jw_player_admin = {
    attach: function (context, settings) {
      var resp = '#edit-settings-responsive';
      var width_suffix = '.form-item-settings-width .field-suffix';

      // If responsive is checked change field suffix to percentage symbol.
      if ($(resp).is(':checked')) {
        $(width_suffix).text('%');
      }

      // When responsive value is changed, change field suffix to '%'
      // otherwise change it back to 'pixels'
      $(resp).change(function () {
        if ($(this).is(':checked')) {
          $(width_suffix).text('%');
        }
        else {
          $(width_suffix).text(Drupal.t('pixels'));
        }
      });
    }
  };

})(jQuery);
