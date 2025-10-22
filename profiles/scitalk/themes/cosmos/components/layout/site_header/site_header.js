(function($, Drupal) {
  Drupal.behaviors.stickyHeader = {
    attach(context) {
      // Add a class when the site header is sticky (position fixed is triggered)
      // See: https://davidwalsh.name/detect-sticky
      const el = document.querySelector(".site-header");
      const observer = new IntersectionObserver(
        ([e]) =>
          e.target.classList.toggle("is-sticky", e.intersectionRatio < 1),
        { threshold: [1] }
      );

      observer.observe(el);
    },
  };
})(jQuery, Drupal);
