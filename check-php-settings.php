<?php
/**
 * Check PHP Settings for Large File Support
 */

echo "=== PHP Settings Check for Large Files ===\n\n";

$required = [
    'upload_max_filesize' => '100M',
    'post_max_size' => '100M',
    'max_execution_time' => '3600',
    'memory_limit' => '512M'
];

$current = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];

function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

$warnings = [];
$errors = [];

echo "Current PHP Settings:\n";
echo "--------------------\n";

foreach ($current as $key => $value) {
    $requiredBytes = convertToBytes($required[$key]);
    $currentBytes = convertToBytes($value);
    
    echo sprintf("%-25s: %-10s", $key, $value);
    
    if ($currentBytes >= $requiredBytes) {
        echo " ✓ OK\n";
    } else {
        echo " ✗ TOO LOW (need: {$required[$key]})\n";
        if (in_array($key, ['upload_max_filesize', 'post_max_size'])) {
            $errors[] = $key;
        } else {
            $warnings[] = $key;
        }
    }
}

echo "\n";

if (count($errors) > 0) {
    echo "❌ ERRORS - These must be fixed for large file uploads:\n";
    foreach ($errors as $key) {
        echo "  - $key: {$current[$key]} (need: {$required[$key]})\n";
    }
    echo "\n";
    echo "To fix:\n";
    echo "1. Find your php.ini: php --ini\n";
    echo "2. Edit php.ini and set:\n";
    foreach ($errors as $key) {
        echo "   $key = {$required[$key]}\n";
    }
    echo "3. Restart your web server\n";
    echo "\n";
    echo "OR use the project php.ini:\n";
    echo "  php -S localhost:8000 -c php.ini\n";
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠ WARNINGS - Recommended for large files:\n";
    foreach ($warnings as $key) {
        echo "  - $key: {$current[$key]} (recommended: {$required[$key]})\n";
    }
    echo "\n";
}

if (count($errors) === 0 && count($warnings) === 0) {
    echo "✓ All settings are OK for large file uploads (65MB, 2+ hours)!\n";
} elseif (count($errors) === 0) {
    echo "✓ File upload settings are OK, but some optimizations recommended.\n";
}

echo "\n";
echo "PHP Configuration File Location:\n";
$iniPath = php_ini_loaded_file();
if ($iniPath) {
    echo "  Loaded: $iniPath\n";
} else {
    echo "  No configuration file loaded\n";
}

$scannedInis = php_ini_scanned_files();
if ($scannedInis) {
    echo "  Scanned: $scannedInis\n";
}
?>


