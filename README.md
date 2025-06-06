# MeowNow 😸

> [!CAUTION]
> As of June 1, 2025, MeowNow has ceased development. I, personally, assume this to be temporary but will update this section if necessary.

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

## Project Structure

```
meownow/
├── config/           # Configuration files
│   ├── .env         # Environment variables (not in repo)
│   └── config.php   # PHP configuration
├── docker/          # Docker configuration
│   ├── config/      # Docker-specific config
│   ├── scripts/     # Docker scripts
│   └── Dockerfile   # Main Dockerfile
├── docs/           # Documentation
│   ├── api-docs.md
│   ├── privacypolicy.md
│   └── LICENSE
├── public/         # Public web root
│   ├── api/       # API endpoints
│   ├── css/       # Stylesheets
│   ├── js/        # JavaScript files
│   └── images/    # Static images
├── src/           # Source code
│   ├── api/       # API implementation
│   └── utils/     # Utility functions
└── vendor/        # Composer dependencies
```

## Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/meownow.git
   cd meownow
   ```

2. Copy the environment file:
   ```bash
   cp config/.env.example config/.env
   ```

3. Edit `config/.env` with your AWS credentials and configuration.

4. Install dependencies:
   ```bash
   composer install
   ```

5. Build and start Docker container:
   ```bash
   docker-compose up -d
   ```

6. Access the website at http://localhost:8080

## API Documentation

The API documentation is available at:
- [Web Version](http://localhost:8080/api-docs.html)
- [Markdown Version](docs/api-docs.md)

### Basic Usage

```bash
# Get a random cat image (redirects to the image URL)
curl https://meownow.app/api/v1/random

# Get JSON metadata about a random cat image
curl https://meownow.app/api/v1/random?format=json

# Get just the URL as plain text
curl https://meownow.app/api/v1/random?format=url

# Download the image directly
curl -o cat.jpg https://meownow.app/api/v1/random?format=image
```

### Using in HTML

```html
<img src="https://meownow.app/api/v1/random" alt="Random Cat">
```

## Development

### Prerequisites

- PHP 8.0 or higher
- Composer
- Docker and Docker Compose
- AWS account with S3 access

### Local Development

1. Clone the repository
2. Copy and configure `.env`
3. Install dependencies
4. Run `docker-compose up -d`

### Testing

```bash
composer test
```

## License

See [LICENSE](docs/LICENSE) for details.

## Credits

Created by [wbreiler.com](https://wbreiler.com)

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

## Privacy Policy

We take user privacy seriously. Please review our [Privacy Policy](privacypolicy.md) for details on how we handle user data.

## Docker Setup

This project includes a Docker setup for easy deployment and development.

### Prerequisites

- Docker
- Docker Compose

### Quick Start

1. Copy the example environment file:
   ```bash
   cp docker/config/.env.example .env
   ```

2. Edit the `.env` file with your AWS credentials and configuration.

3. Build and start the container:
   ```bash
   docker-compose up -d
   ```

4. Access the website at http://localhost:8080

### Configuration

All Docker-related files are stored in the `./docker` directory:
- `docker/Dockerfile`: Main Docker configuration
- `docker/entrypoint.sh`: Container startup script
- `docker/config/.env.example`: Example environment variables

### Development

For development, you can uncomment the volume mounts in `docker-compose.yml` to override files in the container with your local files:

```yaml
volumes:
  - ./css:/var/www/html/css
  - ./js:/var/www/html/js
  - ./api:/var/www/html/api
```
