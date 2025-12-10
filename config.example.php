<?php
// Copy this file to config.php and fill in your actual API keys

// API Keys - Replace with your actual keys
define('ASSEMBLYAI_API_KEY', 'your_assemblyai_key_here');
define('CLAUDE_API_KEY', 'your_anthropic_key_here');
define('CAPTCHA_SITE_KEY', 'your_captcha_site_key_here');
define('CAPTCHA_SECRET', 'your_captcha_secret_here');

// File Upload Settings
define('MAX_FILE_SIZE', 25 * 1024 * 1024); // 25MB
define('ALLOWED_EXTENSIONS', ['m4a', 'mp3', 'wav', 'mp4', 'webm']);
define('ALLOWED_MIME_TYPES', [
    'audio/mp4',
    'audio/mpeg',
    'audio/wav',
    'audio/x-m4a',
    'audio/webm',
    'video/mp4',
    'video/webm'
]);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Rate Limiting
define('MAX_REQUESTS_PER_HOUR', 10);
define('RATE_LIMIT_FILE', __DIR__ . '/rate_limit.json');

// API Endpoints
define('ASSEMBLYAI_UPLOAD_URL', 'https://api.assemblyai.com/v2/upload');
define('ASSEMBLYAI_TRANSCRIPT_URL', 'https://api.assemblyai.com/v2/transcript');
define('CLAUDE_API_URL', 'https://api.anthropic.com/v1/messages');

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Ensure rate limit file exists
if (!file_exists(RATE_LIMIT_FILE)) {
    file_put_contents(RATE_LIMIT_FILE, json_encode([]));
}
