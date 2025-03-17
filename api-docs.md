# MeowNow API Documentation

Welcome to the MeowNow API! This simple API allows you to get random cat images programmatically.

## Get Random Cat

```
GET https://api.meownow.app/api/v1/
```

### Description

Returns a random cat image from our collection. By default, this endpoint redirects to the image URL.

### Parameters

| Parameter | Type    | Required | Description                                                |
|-----------|---------|----------|------------------------------------------------------------|
| format    | string  | No       | Response format: 'redirect' (default), 'json', 'image', or 'url' |
| width     | integer | No       | Desired image width (reserved for future use)              |
| height    | integer | No       | Desired image height (reserved for future use)             |

### Response Formats

- **redirect**: Redirects to the image URL (default)
- **json**: Returns JSON with image metadata
- **image**: Returns the actual image file
- **url**: Returns just the image URL as plain text

### Examples

#### Basic Usage (Redirect)

```bash
curl https://api.meownow.app/api/v1/
```

Response: HTTP 302 redirect to the image URL

#### Get JSON Response

```bash
curl https://api.meownow.app/api/v1/?format=json
```

Response:
```json
{
  "success": true,
  "image": {
    "key": "cat_1234567890.jpg",
    "url": "https://meownowcompressed.s3.amazonaws.com/cat_1234567890.jpg",
    "filename": "cat_1234567890.jpg",
    "lastModified": "2025-03-15 12:34:56"
  },
  "api_version": "v1"
}
```

#### Get Image URL as Text

```bash
curl https://api.meownow.app/api/v1/?format=url
```

Response:
```
https://meownowcompressed.s3.amazonaws.com/cat_1234567890.jpg
```

#### Download Image Directly

```bash
curl -o cat.jpg https://api.meownow.app/api/v1/?format=image
```

Response: The actual image file

#### Using in HTML

```html
<img src="https://api.meownow.app/api/v1/" alt="Random Cat">
```

Result: Displays a random cat image that changes on page refresh

#### Using with JavaScript

```javascript
fetch('https://api.meownow.app/api/v1/?format=json')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      document.getElementById('cat-image').src = data.image.url;
    }
  });
```

## Rate Limits

Please be respectful with your API usage. While we currently do not enforce strict rate limits, excessive requests may be throttled.

## Terms of Use

By using the MeowNow API, you agree to:
- Not use the API for any illegal purposes
- Not attempt to overload or crash our servers
- Provide attribution to MeowNow when using our images in your projects

## Privacy

We log API requests with anonymized IP addresses for monitoring and debugging purposes. Logs are automatically deleted after 30 days. See our [Privacy Policy](privacypolicy.md) for more details. 