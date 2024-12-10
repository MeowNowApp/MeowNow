document.addEventListener("DOMContentLoaded", () => {
  const catImage = document.getElementById("cat-image");
  const refreshButton = document.getElementById("refresh-button");

  const fetchRandomCat = async () => {
    try {
      const response = await fetch("list.php");
      const cats = await response.json();

      if (!Array.isArray(cats)) {
        throw new Error('Response is not an array');
      }

      const randomCat = cats[Math.floor(Math.random() * cats.length)];
      console.log("Random Cat URL:", randomCat);  // Log the URL from list.php

      // Directly set the image source without modification
      catImage.src = randomCat;
      
      console.log("Final Image URL:", catImage.src);  // Log after setting src
    } catch (error) {
      console.error("Error fetching or processing cat list:", error);
    }
  };

  refreshButton.addEventListener("click", fetchRandomCat);
  fetchRandomCat(); // Load a cat on page load
});