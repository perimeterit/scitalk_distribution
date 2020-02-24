
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