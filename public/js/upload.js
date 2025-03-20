// upload.js: Handle file input and display selected file names

document.addEventListener("DOMContentLoaded", function () {
  const fileInput = document.getElementById("catImage");
  const fileNameDisplay = document.getElementById("file-name-display");

  if (fileInput && fileNameDisplay) {
    fileInput.addEventListener("change", function () {
      const files = this.files;
      if (files.length > 0) {
        const fileNames = Array.from(files).map((file) => file.name);
        fileNameDisplay.textContent = `Selected files: ${fileNames.join(", ")}`;
      } else {
        fileNameDisplay.textContent = ""; // Clear if no files are selected
      }
    });
  }
});