(function ($) {
  Drupal.behaviors.scitalkLoadChildren = {
    attach: function (context, settings) {


    $('.collection .toggle-children', context).click(function() {
      var collection_id = $(this).data('collection-id');
      // If it's currently closed
      if ($(this).attr('aria-expanded') == 'false') {
        // Expand this one
        $(this).attr('aria-expanded', 'true');
        console.log('Show ' + collection_id);
        $(this).children('span').html('Hide');
        $('#collection-children--' + collection_id).attr('aria-hidden','false');
      }
      // If it's currently open
      else {
        $(this).attr('aria-expanded', 'false');
        $(this).children('span').html('Show');
        console.log('Hide ' + collection_id);
        $('#collection-children--' + collection_id).attr('aria-hidden','true');
      }
    })

      // Attach ajax action click event of each show more button
      // $('.node.collection .show-children', context).each(this.loadMore);


    },

    // Load by ajax - keep this for awhile in case we want to try this again
    // loadMore: function (idx, button, context) {
    //   console.log('load more');
    //   // Get the Node id
    //   var nid = $(this).data('node-id');
    //   // Everything we need to specify about the view.
    //   var view_info = {
    //     view_name: 'scitalk_collection_children',
    //     view_display_id: 'card_view',
    //     view_args: nid + '/' + nid,
    //     view_dom_id: 'collection-children-' + nid
    //   };
    //
    //   // Details of the ajax action.
    //   var ajax_settings = {
    //     submit: view_info,
    //     url: '/views/ajax',
    //     element: button,
    //     event: 'click'
    //   };
    //
    //   Drupal.ajax(ajax_settings);
    // }

  };
})(jQuery);
