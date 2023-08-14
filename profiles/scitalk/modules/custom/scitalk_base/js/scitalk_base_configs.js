(function ($) {
    Drupal.behaviors.fillConfigurationMappingName = {
      attach: function (context, settings) {
        const vocab_config_name = $(".scitalk-base-form input#edit-label");
        const site_vocab = $("#edit-site-vocabulary-type");
        const site_vocab_machine_name = $(".scitalk-base-form input#edit-id");
  
        $(site_vocab, context).on("change", function () {
            let sel = $("#edit-site-vocabulary-type option:selected" ).text();
            $(vocab_config_name).val(sel);
            $(site_vocab_machine_name).val($("#edit-site-vocabulary-type").val());
        });
      },
    };
})(jQuery);