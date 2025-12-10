# Testing Guide

## Quick Start for Local Testing

### 1. Enable Development Mode

Edit `config.php` and set:
```php
define('DEV_MODE', true);
```

This will:
- Disable CAPTCHA requirement
- Allow testing without HTTPS
- Skip Basic Auth (if using .htaccess.dev)

### 2. Set Up API Keys (Required for Full Testing)

You need to add your API keys to `config.php`:

1. **AssemblyAI** (Required for transcription):
   - Sign up: https://www.assemblyai.com/
   - Get API key from dashboard
   - Add to config: `define('ASSEMBLYAI_API_KEY', 'your_key_here');`

2. **Anthropic Claude** (Required for summarization):
   - Sign up: https://www.anthropic.com/
   - Get API key from console
   - Add to config: `define('CLAUDE_API_KEY', 'your_key_here');`

3. **Google reCAPTCHA** (Optional for dev mode):
   - Only needed for production
   - Can skip for local testing

### 3. Start Development Server

**Option 1: Using the startup script**
```bash
./start-dev.sh
```

**Option 2: Manual start**
```bash
# Use development .htaccess (no HTTPS, no Basic Auth)
cp .htaccess.dev .htaccess

# Enable dev mode
sed -i "s/define('DEV_MODE', false);/define('DEV_MODE', true);/" config.php

# Start server
php -S localhost:8000
```

### 4. Open in Browser

Navigate to: http://localhost:8000

### 5. Test the Application

1. **Without API Keys** (UI Testing):
   - You can test the file upload interface
   - Drag and drop functionality
   - File validation
   - UI responsiveness

2. **With API Keys** (Full Testing):
   - Upload an audio file (.m4a, .mp3, .wav, .mp4, .webm)
   - Wait for transcription
   - View English and Turkish summaries
   - Test copy/download functionality

## Testing Checklist

- [ ] Environment setup (run `php test-setup.php`)
- [ ] File upload interface loads
- [ ] Drag and drop works
- [ ] File validation (type, size)
- [ ] API keys configured
- [ ] Transcription works (with API key)
- [ ] Summarization works (with API key)
- [ ] Results display correctly
- [ ] Copy to clipboard works
- [ ] Download functionality works
- [ ] Error handling works
- [ ] Mobile responsiveness

## Common Issues

### "CAPTCHA verification failed"
- Solution: Enable DEV_MODE in config.php

### "File upload failed"
- Check file size (max 25MB)
- Check file format (must be .m4a, .mp3, .wav, .mp4, .webm)
- Check uploads/ directory permissions

### "Transcription service unavailable"
- Check AssemblyAI API key
- Check API account balance
- Verify internet connection

### "Failed to get summary from AI service"
- Check Claude API key
- Check API account credits
- Verify API endpoint is accessible

## Production Deployment

Before deploying to production:

1. Set `DEV_MODE` to `false` in config.php
2. Use production `.htaccess` (with HTTPS and Basic Auth)
3. Set up Google reCAPTCHA keys
4. Configure Basic Auth with `.htpasswd`
5. Ensure HTTPS is enabled
6. Set proper file permissions

