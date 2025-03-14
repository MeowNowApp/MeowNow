# MeowNow 😸

A simple and elegant web application for displaying and sharing random cat images using AWS S3 for storage.

## Features

- **Random Cat Display**: Shows random cat images from your S3 bucket
- **Simple Interface**: Clean, responsive design that works on all devices
- **Image Upload**: Users can upload their own cat photos directly to S3
- **On-the-fly Compression**: Uses AWS Lambda to compress images when they're accessed
- **EXIF Orientation Fix**: Automatically corrects image orientation based on EXIF data
- **Keyboard Shortcuts**: Press spacebar to show another cat
- **Fallback CSS**: Multiple layers of CSS fallback to ensure the site always looks good
- **Cloud Storage**: Uses AWS S3 for reliable, scalable image storage
- **Public API**: Access random cat images programmatically via API

## AWS Architecture

### S3 Storage

The application uses a two-bucket approach for image storage:

- **Raw Bucket (meownowraw)**: Stores original, unprocessed images uploaded by users
- **Compressed Bucket (meownowcompressed)**: Stores processed, optimized images for display

### Lambda Image Processing

The application uses AWS Lambda for on-the-fly image compression and processing:

1. Original images are uploaded directly to the raw S3 bucket
2. When an image is requested, a Lambda function is triggered
3. The Lambda function retrieves the original image from the raw bucket, compresses it, and saves it to the compressed bucket
4. The web application serves images from the compressed bucket
5. Processed images are cached in the compressed bucket for improved performance

This serverless approach eliminates the need for server-side compression during upload and allows for dynamic resizing and optimization based on the requesting device.

## Environment Variables

This application uses environment variables for configuration. For security reasons, details about specific variables are not documented here. Refer to the `.env.example` file for the required configuration.

## Directory Structure

- `index.html` - Main page that displays random cat images
- `script.js` - JavaScript for fetching and displaying cat images
- `style.css` - Main stylesheet
- `backup.css` - Fallback stylesheet if the main one fails to load
- `upload.php` - Handles image uploads to S3
- `list.php` - Returns a JSON list of available cat images from S3
- `cat_list.json` - Local cache of uploaded images (fallback for S3)
- `logs/` - Directory for log files
- `vendor/` - Directory for Composer dependencies (AWS SDK)

## Image Upload

The application allows users to upload their own cat photos with the following features:

- Supports JPG and PNG formats
- Maximum file size: 50MB per file, 250MB total per upload session
- EXIF orientation correction
- Unique filename generation to prevent conflicts
- Direct upload to S3 with public-read ACL

## Browser Compatibility

MeowNow works on all modern browsers including:

- Google Chrome
- Mozilla Firefox
- Apple Safari
- Microsoft Edge

## Performance Optimizations

- CSS and image preloading
- Multiple CSS fallback mechanisms
- Next image preloading for faster browsing
- Cloud-based image storage for reliability and performance
- Serverless image processing with AWS Lambda:
  - On-demand compression reduces storage requirements
  - Dynamic resizing based on client device capabilities
  - Caching of processed images for faster subsequent access
  - Reduced load on the web server by offloading image processing
  - Automatic scaling to handle traffic spikes

## API

MeowNow provides a simple API for accessing random cat images programmatically. The API is available at `https://api.meownow.app/`.

### Basic Usage

```bash
# Get a random cat image (redirects to the image URL)
curl https://api.meownow.app/

# Get JSON metadata about a random cat image
curl https://api.meownow.app/?format=json

# Get just the URL as plain text
curl https://api.meownow.app/?format=url

# Download the image directly
curl -o cat.jpg https://api.meownow.app/?format=image
```

### Using in HTML

```html
<img src="https://api.meownow.app/" alt="Random Cat">
```

For more details, see the [API Documentation](api-docs.html).

## Privacy Policy

We take user privacy seriously. Please review our [Privacy Policy](privacypolicy.md) for details on how we handle user data.

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
