const body = document.body;
const scrollClass = "scroll-down";
let lastScroll = 0;

window.addEventListener("scroll", () => {
  const currentScroll = window.pageYOffset;
  if (currentScroll == 0) {
    body.classList.remove(scrollClass);
    return;
  }

  // On scrolling down, add the Scroll class if it isn't already there
  if (currentScroll > lastScroll && !body.classList.contains(scrollClass)) {
    body.classList.add(scrollClass);
  }
  lastScroll = currentScroll;
});