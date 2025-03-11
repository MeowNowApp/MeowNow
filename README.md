# Random Cat Picture Website

A fun personal project that displays a random picture of my cats on each page refresh. Images are served from the `./cats` directory, using simple HTML, CSS, JavaScript, and PHP.

## How It Works

- **Random Image Selection:** JavaScript fetches a list of images from a PHP script and displays one at random.
- **Dynamic Updates:** Any `.jpg` or `.png` files added to the `./cats` directory are automatically included.

## File Structure

```
RandomCat/
├── index.html       # The main webpage
├── style.css        # Basic styling for the page
├── script.js        # Handles fetching and displaying random images
├── list.php         # Generates a list of image files in the cats directory
|── cats/            # Where all the cat images are stored
└── .env             # Environment variables (not included in this example, however a template is provided in .env.example for reference)
```

## Purpose

Hosted locally via your favorite web server, or on `cats.wbreiler.com`, this project is a lighthearted way to share random cat pictures.

## Notes

This is a personal project and not meant for general use — just a fun way to showcase my cats!

---

🐾 Enjoy the cat pictures! 🐱
