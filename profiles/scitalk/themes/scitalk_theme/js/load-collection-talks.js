(function ($) {
  Drupal.behaviors.scitalkAjaxLoad = {
    attach: function (context, settings) {
      // Attach ajax action click event of each view column.
      $('.node.collection .show-children', context).each(this.attachLink);
      $('.collection-wrapper + .hide-children').click(function() {
        $(this).siblings('.advanced-view-display').slideUp()
        ;
      })
    },

    attachLink: function (idx, button) {
      // Get the Node id
      var nid = $(this).data('node-id');
      // Everything we need to specify about the view.
      var view_info = {
        view_name: 'scitalk_collection_children',
        view_display_id: 'card_view',
        view_args: nid + '/' + nid,
        view_dom_id: 'collection-children-' + nid
      };

      // Details of the ajax action.
      var ajax_settings = {
        submit: view_info,
        url: '/views/ajax',
        element: button,
        event: 'click'
      };

      Drupal.ajax(ajax_settings);
    }
  };
})(jQuery);
