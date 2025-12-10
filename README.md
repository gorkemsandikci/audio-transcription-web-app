# Audio Transcription & Summary Web Application

A PHP-based web application that accepts audio file uploads, transcribes them using AssemblyAI, and generates structured summaries in both English and Turkish using Anthropic Claude API.

## Features

- 🎤 Audio file upload with drag-and-drop support
- 📝 Automatic transcription using AssemblyAI
- 🤖 AI-powered summarization using Claude API
- 🌍 Bilingual summaries (English & Turkish)
- 🔒 Basic HTTP authentication
- 🛡️ CAPTCHA verification (Google reCAPTCHA v2)
- ⚡ Rate limiting (10 requests per hour per IP)
- 📱 Responsive design with modern UI
- 💾 Copy and download functionality for results

## Supported Audio Formats

- `.m4a` (iPhone voice recordings)
- `.mp3`
- `.wav`
- `.mp4`
- `.webm`

Maximum file size: 25MB

## Setup Instructions

### 1. Prerequisites

- PHP 7.4 or higher
- Apache web server with mod_rewrite enabled
- cURL extension enabled
- HTTPS certificate (required for reCAPTCHA)

### 2. API Keys Setup

1. **AssemblyAI**:
   - Sign up at [AssemblyAI](https://www.assemblyai.com/)
   - Get your API key from the dashboard
   - Free tier includes 5 hours of transcription

2. **Anthropic Claude**:
   - Sign up at [Anthropic](https://www.anthropic.com/)
   - Get your API key from the console
   - Add credits to your account

3. **Google reCAPTCHA**:
   - Register at [Google reCAPTCHA](https://www.google.com/recaptcha/admin)
   - Create a reCAPTCHA v2 site
   - Get your Site Key and Secret Key

### 3. Configuration

1. Copy `config.php` and update with your API keys:
```php
define('ASSEMBLYAI_API_KEY', 'your_assemblyai_key_here');
define('CLAUDE_API_KEY', 'your_anthropic_key_here');
define('CAPTCHA_SITE_KEY', 'your_captcha_site_key_here');
define('CAPTCHA_SECRET', 'your_captcha_secret_here');
```

2. Set up Basic Authentication:
```bash
htpasswd -c .htpasswd username
```
Enter a password when prompted. Update the `.htaccess` file with the correct path to `.htpasswd`.

3. Ensure uploads directory is writable:
```bash
chmod 755 uploads/
```

### 4. File Permissions

Ensure proper permissions:
- `uploads/` directory: 755
- `rate_limit.json`: 666 (will be created automatically)
- `.htpasswd`: 644

### 5. Apache Configuration

Make sure your Apache server:
- Has `mod_rewrite` enabled
- Allows `.htaccess` overrides
- Has PHP enabled

## Usage

1. Access the application via HTTPS
2. Enter Basic Auth credentials
3. Drag and drop or browse for an audio file
4. Complete the CAPTCHA verification
5. Click "Process Audio"
6. Wait for transcription and summary generation
7. View results in English and Turkish
8. Copy or download results as needed

## Security Features

- Basic HTTP authentication
- CAPTCHA verification
- File type and size validation
- MIME type checking
- Rate limiting (10 requests/hour/IP)
- HTTPS enforcement
- Input sanitization
- Secure file handling

## Project Structure

```
audio-transcription-web-app/
├── index.php              # Main upload interface
├── process.php            # File processing handler
├── config.php             # Configuration (API keys, settings)
├── .htaccess              # Apache configuration & Basic Auth
├── .htpasswd              # Basic Auth credentials
├── rate_limit.json        # Rate limiting data
├── uploads/               # Temporary audio storage
└── assets/
    ├── css/
    │   └── style.css      # Custom styles
    └── js/
        └── main.js         # Frontend JavaScript
```

## Troubleshooting

### Transcription fails
- Check AssemblyAI API key and account balance
- Verify file format is supported
- Check file size is under 25MB

### Summary generation fails
- Check Claude API key and account credits
- Verify API endpoint is accessible
- Check PHP cURL extension is enabled

### CAPTCHA not working
- Ensure HTTPS is enabled (required for reCAPTCHA)
- Verify Site Key and Secret Key are correct
- Check browser console for errors

### Rate limiting issues
- Wait 1 hour between requests from the same IP
- Check `rate_limit.json` file permissions

## License

This project is provided as-is for personal or commercial use.

## Support

For issues or questions, please check:
- AssemblyAI Documentation: https://www.assemblyai.com/docs
- Anthropic Claude API Docs: https://docs.anthropic.com
- Google reCAPTCHA Docs: https://developers.google.com/recaptcha

