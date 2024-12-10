document.addEventListener("DOMContentLoaded", () => {
  const catImage = document.getElementById("cat-image");
  const refreshButton = document.getElementById("refresh-button");

  const fetchRandomCat = () => {
      // Fetch the list of images from the server
      fetch("list.php")
          .then(response => response.json())
          .then(cats => {
              // Pick a random image from the list
              const randomCat = cats[Math.floor(Math.random() * cats.length)];
              catImage.src = `cats/${randomCat}`;
          })
          .catch(error => console.error("Error fetching cat list:", error));
  };

  refreshButton.addEventListener("click", fetchRandomCat);
  fetchRandomCat(); // Load a cat on page load
});