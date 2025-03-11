document.addEventListener("DOMContentLoaded", () => {
  const catImage = document.getElementById("cat-image");
  const refreshButton = document.getElementById("refresh-button");

  const fetchRandomCat = async () => {
    try {
      const response = await fetch("./list.php"); // Updated to fetch from list.php in the same directory

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const cats = await response.json();

      if (!Array.isArray(cats)) {
        throw new Error("Response is not an array");
      }

      // Select a random image
      const randomCat = cats[Math.floor(Math.random() * cats.length)];

      // Update the image URL to fetch from the local directory
      const imageUrl = `/cats/${randomCat}`;

      // Set the image source to the local image URL
      catImage.src = imageUrl;
    } catch (error) {
      console.error("Fetch Error:", error);
    }
  };

  refreshButton.addEventListener("click", fetchRandomCat);
  fetchRandomCat(); // Load a cat on page load
});
