// script.js: Fetch and display random cat images

document.addEventListener("DOMContentLoaded", function() {
    const catImage = document.getElementById("cat-image");
    const refreshButton = document.getElementById("refresh-button");
    const imageInfo = document.getElementById("image-info");
    const catContainer = document.getElementById("cat-container");
    
    // Add loading class to container when image is loading
    catImage.addEventListener("load", function() {
        catContainer.classList.remove("loading");
        catImage.style.display = "block"; // Show the image once loaded
        console.log("Image loaded successfully:", catImage.src);
    });
    
    catImage.addEventListener("error", function() {
        catContainer.classList.remove("loading");
        imageInfo.textContent = "Error loading image. Please try again.";
        console.error("Failed to load image:", catImage.src);
    });
    
    // Simple function to get a list of cat images directly
    async function getDirectCatList() {
        try {
            console.log("Getting direct cat list...");
            const response = await fetch("list.php");
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            const data = await response.json();
            console.log("Direct cat list:", data);
            return data;
        } catch (error) {
            console.error("Error getting direct cat list:", error);
            return [];
        }
    }
    
    // Function to display a random cat image
    async function displayRandomCat() {
        // Show loading state
        catContainer.classList.add("loading");
        catImage.style.opacity = "0";
        catImage.style.display = "none"; // Hide while loading
        imageInfo.textContent = "Loading a random cat...";
        
        try {
            // Get the list of cat images
            const cats = await getDirectCatList();
            console.log("Cats available:", cats.length);
            
            if (!cats || cats.length === 0) {
                catImage.src = "images/no-cats.jpg";
                catImage.alt = "No cat images available";
                catImage.style.display = "block";
                catImage.style.opacity = "1";
                imageInfo.textContent = "";
                return;
            }
            
            // Select a random cat from the list
            const randomIndex = Math.floor(Math.random() * cats.length);
            const randomCat = cats[randomIndex];
            console.log("Selected cat:", randomCat);
            
            // Use the URL from our proxy endpoint
            const imageUrl = randomCat.url;
            console.log("Loading image from URL:", imageUrl);
            
            // Set the image source
            catImage.src = imageUrl;
            catImage.alt = "A random cat";
            catImage.style.display = "block"; // Ensure image is visible
            catImage.style.opacity = "1";
            
            // Clear the image info text
            imageInfo.textContent = "";
            
            // Preload the next image for faster experience
            if (cats.length > 1) {
                const nextIndex = (randomIndex + 1) % cats.length;
                const nextCat = cats[nextIndex];
                const preloadImage = new Image();
                preloadImage.src = nextCat.url;
            }
        } catch (error) {
            console.error("Error displaying random cat:", error);
            catImage.src = "images/error.jpg";
            catImage.alt = "Error loading cat image";
            catImage.style.display = "block";
            catImage.style.opacity = "1";
            imageInfo.textContent = "";
        }
    }
    
    // Display a random cat when the page loads
    displayRandomCat();
    
    // Display a new random cat when the refresh button is clicked
    if (refreshButton) {
        refreshButton.addEventListener("click", function() {
            // Disable button temporarily to prevent rapid clicking
            refreshButton.disabled = true;
            
            displayRandomCat().finally(() => {
                // Re-enable button after image loads or after 2 seconds (whichever comes first)
                setTimeout(() => {
                    refreshButton.disabled = false;
                }, 2000);
            });
        });
    }
    
    // Add keyboard shortcut (spacebar) to show another cat
    document.addEventListener("keydown", function(event) {
        if (event.code === "Space" && !refreshButton.disabled) {
            event.preventDefault(); // Prevent page scrolling
            refreshButton.click();
        }
    });
});
