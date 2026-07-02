(function ($, Drupal, drupalSettings) {

 /**
   * Responsive tables for Latest Talks table display
   */
  Drupal.behaviors.responsiveTable = {
    attach: function (context, settings) {
      $.responsiveTables("800px");
    },
  };

  /**
   * Make sure type details element is always collapsed
   * By default BEF will make this open by default if there is a selection
   */
  Drupal.behaviors.scitalk_fixDetailsFilter = {
    attach: function (context, settings) {
      $(".advanced-view-header details[open]").removeAttr("open");
    },
  };

  /**
   * Teaser hide/show toggle
   */
  Drupal.behaviors.toggleAbstract = {
    attach: function (context, settings) {
      // Remove any paragraphs contianing only a nbsp
      $(".field--name-field-talk-abstract p")
        .filter(function () {
          return this.innerHTML == "&nbsp;";
        })
        .remove();

      // Toggle the full content
      $(".node.teaser .show-more", context).click(function () {
        var show_text = $(this).html();
        if ($(this).hasClass("show")) {
          $(this).html($(this).attr("data-hide-text"));
        }
        if ($(this).hasClass("hide")) {
          $(this).html($(this).attr("data-show-text"));
        }
        $(this)
          .parents(".node-content")
          .toggleClass("collapse-abstract")
          .toggleClass("show-abstract");
        $(this).toggleClass("show").toggleClass("hide");
      });
    },
  };

  /*
   * Source landing page - make longer text hide overflow
   */
  Drupal.behaviors.toggleSourceDesc = {
    attach: function (context, settings) {
      var source_desc_el = $(
        ".group--source-repository.group--page-header .source-description",
        context
      );
      var source_desc_height = source_desc_el.height();
      var show_more_text = "Show more";
      var hide_more_text = "Show less";
      if (source_desc_height > 300) {
        source_desc_el.addClass("source-desc-overflow");
        source_desc_el.after(
          '<a class="source-show-more">' + show_more_text + "</a>"
        );
      }

      var toggle_el = once("sourceDescription", "a.source-show-more", context);
      $(toggle_el, context).click(function () {
        source_desc_el.toggleClass("open");
        if (source_desc_el.hasClass("open")) {
          $(this).html(hide_more_text);
        } else {
          $(this).html(show_more_text);
        }
      });
    },
  };

  /**
   * Related talks
   */
  Drupal.behaviors.toggleRelatedPosts = {
    attach: function (context, settings) {
      if (typeof context.querySelector === "function") {
        let relatedPosts = context.querySelector(".related-talks-view");
        if (relatedPosts) {
          context
            .querySelector("h2.related-talks-toggle")
            ?.addEventListener("click", (event) => {
              relatedPosts.classList.toggle("show");
            });
        }
      }
    },
  };

  // Override Core Ajax scroll to top behaviour
  // Adjusts scroll position to accommodate sticky header
  Drupal.AjaxCommands.prototype.viewsScrollTop = function (ajax, response) {
    // Scroll to the top of the view. This will allow users
    // to browse newly loaded content after e.g. clicking a pager
    // link.
    var offset = $(response.selector).offset();

    // We can't guarantee that the scrollable object should be
    // the body, as the view could be embedded in something
    // more complex such as a modal popup. Recurse up the DOM
    // and scroll the first element that has a non-zero top.
    var scrollTarget = response.selector;
    while ($(scrollTarget).scrollTop() === 0 && $(scrollTarget).parent()) {
      scrollTarget = $(scrollTarget).parent();
    }

    // Only scroll upward.
    if (offset.top - 10 < $(scrollTarget).scrollTop()) {
      $(scrollTarget).animate({ scrollTop: offset.top - 110 }, 500);
    }
  };
})(jQuery, Drupal, drupalSettings);
