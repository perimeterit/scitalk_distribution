
document.addEventListener("DOMContentLoaded", () => {

  // Get the initial value for the selected language
  const langSelect = document.getElementById("transcripts-language-selection");
  const selectedLang = langSelect ? langSelect.value : "en";
  showTranscriptForLanguage(selectedLang);

  // Function to show the transcript for the selected language
  function showTranscriptForLanguage(selectedLang) {
    let transcriptItems = document.querySelectorAll(".transcripts-item");
    transcriptItems.forEach((item) => {
      if (item.getAttribute("data-transcript-language") === selectedLang) {
        item.classList.add("transcript-lang-selected");
      } else {
        item.classList.remove("transcript-lang-selected");
      }
    });
  }

  // Show the right transcript on changing the language select value
  document.getElementById("transcripts-language-selection")
    .addEventListener("change", (e) => {
      let selectedLang = e.target.value;
      showTranscriptForLanguage(selectedLang);

    });

  // Toggle Transcript Section
  let toggleButtons = document.querySelectorAll(".transcripts-toggle");

  toggleButtons.forEach(function(toggleButton) {
    toggleButton.addEventListener('click', toggleTranscripts);
  });

  function toggleTranscripts() {
    ariaStatus = toggleButtons[0].getAttribute("aria-expanded");
    if (ariaStatus === "true") {
      toggleButtons[0].setAttribute("aria-expanded", false);
    } else {
      toggleButtons[0].setAttribute("aria-expanded", true);
    }

    let mainWrapper = document.querySelectorAll(".main-content")[0];
    mainWrapper.classList.toggle("transcripts-open");
  }

});
