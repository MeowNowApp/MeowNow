document.addEventListener("DOMContentLoaded", () => {
  const catImage = document.getElementById("cat-image");
  const refreshButton = document.getElementById("refresh-button");

  const fetchRandomCat = async () => {
    try {
      const response = await fetch("list.php");

      // Log the raw response text first
      const rawResponse = await response.text();

      // Try parsing the response
      try {
        const cats = JSON.parse(rawResponse);

        if (!Array.isArray(cats)) {
          throw new Error('Response is not an array');
        }

        // Select a random image
        const randomCat = cats[Math.floor(Math.random() * cats.length)];
        
        // Update the image URL to fetch from the compressed folder in S3
        const imageUrl = `https://randomcatcompressed.s3.amazonaws.com/${randomCat}`;
        
        // Set the image source to the random cat image URL
        catImage.src = imageUrl;
      } catch (parseError) {
        console.error("JSON Parsing Error:", parseError);
        console.error("Could not parse response:", rawResponse);
      }
    } catch (error) {
      console.error("Fetch Error:", error);
    }
  };

  refreshButton.addEventListener("click", fetchRandomCat);
  fetchRandomCat(); // Load a cat on page load
});