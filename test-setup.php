<?php
/**
 * Test Setup Script
 * Run this to check if your environment is ready
 */

echo "=== Audio Transcription App - Setup Test ===\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check PHP version
echo "1. Checking PHP version...\n";
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    $success[] = "PHP version: $phpVersion (OK)";
    echo "   ✓ PHP $phpVersion\n";
} else {
    $errors[] = "PHP version $phpVersion is too old. Need 7.4+";
    echo "   ✗ PHP $phpVersion (Need 7.4+)\n";
}

// Check cURL
echo "\n2. Checking cURL extension...\n";
if (extension_loaded('curl')) {
    $success[] = "cURL extension loaded";
    echo "   ✓ cURL extension is available\n";
} else {
    $errors[] = "cURL extension is not loaded";
    echo "   ✗ cURL extension not found\n";
}

// Check file permissions
echo "\n3. Checking directories and permissions...\n";
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        $success[] = "Created uploads directory";
        echo "   ✓ Created uploads/ directory\n";
    } else {
        $errors[] = "Cannot create uploads directory";
        echo "   ✗ Cannot create uploads/ directory\n";
    }
} else {
    if (is_writable($uploadDir)) {
        $success[] = "uploads/ directory is writable";
        echo "   ✓ uploads/ directory exists and is writable\n";
    } else {
        $errors[] = "uploads/ directory is not writable";
        echo "   ✗ uploads/ directory is not writable\n";
    }
}

// Check config file
echo "\n4. Checking configuration...\n";
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
    
    if (ASSEMBLYAI_API_KEY === 'your_assemblyai_key_here') {
        $warnings[] = "AssemblyAI API key not configured";
        echo "   ⚠ AssemblyAI API key not set\n";
    } else {
        $success[] = "AssemblyAI API key configured";
        echo "   ✓ AssemblyAI API key is set\n";
    }
    
    if (CLAUDE_API_KEY === 'your_anthropic_key_here') {
        $warnings[] = "Claude API key not configured";
        echo "   ⚠ Claude API key not set\n";
    } else {
        $success[] = "Claude API key configured";
        echo "   ✓ Claude API key is set\n";
    }
    
    if (CAPTCHA_SITE_KEY === 'your_captcha_site_key_here') {
        $warnings[] = "CAPTCHA keys not configured (required for production)";
        echo "   ⚠ CAPTCHA keys not set (OK for local testing)\n";
    } else {
        $success[] = "CAPTCHA keys configured";
        echo "   ✓ CAPTCHA keys are set\n";
    }
} else {
    $errors[] = "config.php file not found";
    echo "   ✗ config.php not found\n";
}

// Check rate limit file
echo "\n5. Checking rate limit file...\n";
$rateLimitFile = __DIR__ . '/rate_limit.json';
if (!file_exists($rateLimitFile)) {
    if (file_put_contents($rateLimitFile, json_encode([]))) {
        $success[] = "Created rate_limit.json";
        echo "   ✓ Created rate_limit.json\n";
    } else {
        $warnings[] = "Cannot create rate_limit.json (will be created automatically)";
        echo "   ⚠ Cannot create rate_limit.json (will auto-create)\n";
    }
} else {
    if (is_writable($rateLimitFile)) {
        $success[] = "rate_limit.json is writable";
        echo "   ✓ rate_limit.json exists and is writable\n";
    } else {
        $warnings[] = "rate_limit.json is not writable";
        echo "   ⚠ rate_limit.json is not writable\n";
    }
}

// Summary
echo "\n=== Summary ===\n";
echo "✓ Success: " . count($success) . "\n";
echo "⚠ Warnings: " . count($warnings) . "\n";
echo "✗ Errors: " . count($errors) . "\n\n";

if (count($errors) > 0) {
    echo "ERRORS (must fix):\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

if (count($errors) === 0) {
    echo "✓ Environment is ready!\n";
    echo "\nTo start the development server:\n";
    echo "  php -S localhost:8000\n";
    echo "\nThen open: http://localhost:8000\n";
    echo "\nNote: For local testing, you may want to disable HTTPS redirect in .htaccess\n";
} else {
    echo "✗ Please fix the errors above before proceeding.\n";
    exit(1);
}
?>


