/**
 * @file
 * Toggles the 'dark' and 'light' classes based on the selected colour mode.
 */

((Drupal, once) => {
  /**
   * Attaches behavior to toggle a class based on the selected colour mode.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *  Toggles the 'light' and 'dark' classes on the HTML element based on the
   *  selected mode, and updates the 'data-dark-mode-source' attribute accordingly.
   */
  Drupal.behaviors.darkModeToggle = {
    attach(context) {
      once("darkModeToggle", "html", context).forEach(() => {
        context
          .querySelector(".dark-mode-toggle__button__light")
          .addEventListener("click", () => {
            // When the user explicitly chooses the light mode.
            localStorage.setItem("dark-mode", "light");
            document.documentElement.classList.add("light");
            document.documentElement.setAttribute(
              "data-dark-mode-source",
              "user"
            );
          });

        context
          .querySelector(".dark-mode-toggle__button__dark")
          .addEventListener("click", () => {
            // When the user explicitly chooses the dark mode.
            localStorage.setItem("dark-mode", "dark");
            document.documentElement.classList.remove("light");
            document.documentElement.setAttribute(
              "data-dark-mode-source",
              "user"
            );
          });

        context
          .querySelector(".dark-mode-toggle__button__system")
          .addEventListener("click", () => {
            // Whenever the user explicitly chooses to respect/switch back to the
            // OS preference.
            localStorage.removeItem("dark-mode");
            document.documentElement.classList.toggle(
              "light",
              window.matchMedia("(prefers-color-scheme: dark)").matches
            );
            document.documentElement.setAttribute(
              "data-dark-mode-source",
              "system"
            );
          });

        window
          .matchMedia("(prefers-color-scheme: dark)")
          .addEventListener("change", (e) => {
            // When the system preference changes and the user has chosen to
            // respect the system preference.
            if (
              document.documentElement.getAttribute("data-dark-mode-source") ===
              "system"
            ) {
              document.documentElement.classList.toggle("light", e.matches);
            }
          });
      });
    },
  };
})(Drupal, once);
