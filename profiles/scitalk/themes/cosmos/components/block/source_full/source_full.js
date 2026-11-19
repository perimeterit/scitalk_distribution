(function (Drupal, once) {
  Drupal.behaviors.toggleSourceDesc = {
    attach(context) {
      const showMoreText = "Show more";
      const hideMoreText = "Show less";

      once(
        "sourceDescription",
        ".group--source-repository.group--page-header .source-description",
        context,
      ).forEach((sourceDescEl) => {
        if (sourceDescEl.clientHeight <= 300) {
          return;
        }

        sourceDescEl.classList.add("source-desc-overflow");

        const toggleEl = document.createElement("a");
        toggleEl.className = "source-show-more";
        toggleEl.classList.add("caret-link");
        toggleEl.textContent = showMoreText;
        sourceDescEl.after(toggleEl);

        toggleEl.addEventListener("click", () => {
          const isOpen = sourceDescEl.classList.toggle("open");
          toggleEl.textContent = isOpen ? hideMoreText : showMoreText;
        });
      });
    },
  };
})(Drupal, once);
