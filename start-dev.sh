#!/bin/bash
# Development server startup script

echo "Starting Audio Transcription App - Development Server"
echo "===================================================="
echo ""

# Check if .htaccess.dev exists and copy it
if [ -f ".htaccess.dev" ] && [ ! -f ".htaccess" ]; then
    echo "Using development .htaccess (no HTTPS redirect, no Basic Auth)"
    cp .htaccess.dev .htaccess
fi

# Enable dev mode in config if not already set
if grep -q "define('DEV_MODE', false);" config.php; then
    echo "Enabling DEV_MODE in config.php..."
    sed -i "s/define('DEV_MODE', false);/define('DEV_MODE', true);/" config.php
    echo "✓ Development mode enabled"
fi

echo ""
echo "Starting PHP development server on http://localhost:8000"
echo "Press Ctrl+C to stop"
echo ""
echo "Open your browser and go to: http://localhost:8000"
echo ""
echo "Note: For large files (65MB+), make sure your system php.ini has:"
echo "  upload_max_filesize = 100M"
echo "  post_max_size = 100M"
echo "  max_execution_time = 3600"
echo ""

# Try to use custom php.ini if it exists, otherwise use system default
if [ -f "php.ini" ]; then
    echo "Using custom php.ini from project directory"
    php -S localhost:8000 -c php.ini
else
    echo "Using system php.ini (check settings for large files)"
    php -S localhost:8000
fi

