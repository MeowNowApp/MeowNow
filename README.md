# MeowNow 😸

A simple and elegant web application for displaying and sharing random cat images.

## Features

- **Random Cat Display**: Shows random cat images from your collection
- **Simple Interface**: Clean, responsive design that works on all devices
- **Image Upload**: Users can upload their own cat photos
- **Image Compression**: Automatically compresses uploaded images to save space
- **EXIF Orientation Fix**: Automatically corrects image orientation based on EXIF data
- **Keyboard Shortcuts**: Press spacebar to show another cat
- **Fallback CSS**: Multiple layers of CSS fallback to ensure the site always looks good

## Setup

1. Clone this repository to your web server
2. Ensure the `cats/` and `logs/` directories exist and are writable by the web server
3. Make sure PHP is installed with GD library support for image processing
4. Visit the site in your browser

## Directory Structure

- `index.html` - Main page that displays random cat images
- `script.js` - JavaScript for fetching and displaying cat images
- `style.css` - Main stylesheet
- `backup.css` - Fallback stylesheet if the main one fails to load
- `upload.php` - Handles image uploads with compression and orientation fixes
- `list.php` - Returns a JSON list of available cat images
- `cats/` - Directory where cat images are stored
- `logs/` - Directory for log files

## Image Upload

The application allows users to upload their own cat photos with the following features:

- Supports JPG and PNG formats
- Maximum file size: 50MB per file, 250MB total per upload session
- Automatic compression to reduce file size
- EXIF orientation correction
- Unique filename generation to prevent conflicts

## Browser Compatibility

MeowNow works on all modern browsers including:

- Google Chrome
- Mozilla Firefox
- Apple Safari
- Microsoft Edge

## Performance Optimizations

- CSS and image preloading
- Multiple CSS fallback mechanisms
- Image compression
- Next image preloading for faster browsing

## License

This project is licensed under the Apache License 2.0 - see the [LICENSE](LICENSE) file for details.

This means you are free to:

- Use this software for commercial purposes
- Modify the software
- Distribute the software
- Sublicense the software
- Use the software privately

Under the following conditions:

- You must include the original copyright notice
- You must include the license notice
- For significant modifications, you must state that you changed the files
- If you include a NOTICE file, you must include it in any redistributions

## Credits

Created by [wbreiler.com](https://wbreiler.com)
