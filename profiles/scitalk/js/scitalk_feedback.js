(function($) {
  
    function getUrlParameter(name) {
      let paramVal = '';
      if (typeof URL === "function") {
        let params = (new URL(document.location)).searchParams;
        paramVal = params.get(name) || ''; 
      }
      else {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        paramVal = results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
      }
      return paramVal;
    };

    $('document').ready(function() {
      //find page param val from url
      let page = getUrlParameter('page');
      $('#edit-field-feedback-page-0-value').val(page);

    });
      
   
  })(jQuery);