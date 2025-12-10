<?php
/**
 * Test script to check file upload and validation
 * Usage: php test-file-upload.php <file_path>
 */

require_once 'config.php';

if ($argc < 2) {
    echo "Usage: php test-file-upload.php <file_path>\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "Error: File not found: $filePath\n";
    exit(1);
}

echo "Testing file: $filePath\n";
echo "================================\n\n";

// Get file info
$fileSize = filesize($filePath);
$fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$fileName = basename($filePath);

echo "File Name: $fileName\n";
echo "Extension: $fileExt\n";
echo "Size: " . round($fileSize / 1024 / 1024, 2) . " MB\n\n";

// Check extension
if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
    echo "❌ ERROR: Extension '$fileExt' is not allowed.\n";
    echo "Allowed: " . implode(', ', ALLOWED_EXTENSIONS) . "\n";
    exit(1);
}
echo "✓ Extension is valid\n";

// Check file size
if ($fileSize > MAX_FILE_SIZE) {
    echo "❌ ERROR: File size (" . round($fileSize / 1024 / 1024, 2) . " MB) exceeds limit (" . (MAX_FILE_SIZE / 1024 / 1024) . " MB)\n";
    exit(1);
}
echo "✓ File size is within limit\n";

// Check file headers
$handle = fopen($filePath, 'rb');
if (!$handle) {
    echo "❌ ERROR: Cannot open file for reading\n";
    exit(1);
}

$header = fread($handle, 12);
fclose($handle);

echo "\nFile Header (first 12 bytes): ";
for ($i = 0; $i < 12; $i++) {
    printf("%02X ", ord($header[$i]));
}
echo "\n";

$isValidAudio = false;
$detectedFormat = 'Unknown';

if ($fileExt === 'm4a' || $fileExt === 'mp4') {
    if (substr($header, 4, 4) === 'ftyp') {
        $isValidAudio = true;
        $detectedFormat = 'MP4/M4A';
    }
} elseif ($fileExt === 'mp3') {
    if (substr($header, 0, 3) === 'ID3') {
        $isValidAudio = true;
        $detectedFormat = 'MP3 (ID3 tag)';
    } elseif (ord($header[0]) === 0xFF && (ord($header[1]) & 0xE0) === 0xE0) {
        $isValidAudio = true;
        $detectedFormat = 'MP3 (frame sync)';
    }
} elseif ($fileExt === 'wav') {
    if (substr($header, 0, 4) === 'RIFF') {
        $isValidAudio = true;
        $detectedFormat = 'WAV';
    }
} elseif ($fileExt === 'webm') {
    if (substr($header, 0, 4) === "\x1a\x45\xdf\xa3") {
        $isValidAudio = true;
        $detectedFormat = 'WebM';
    }
}

if ($isValidAudio) {
    echo "✓ File header is valid ($detectedFormat)\n";
} else {
    echo "⚠ WARNING: File header does not match expected format for .$fileExt\n";
    echo "  This may cause issues with AssemblyAI.\n";
}

// Detect MIME type
$mimeType = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
}

echo "\nDetected MIME Type: $mimeType\n";

$mimeMap = [
    'm4a' => 'audio/mp4',
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'mp4' => 'video/mp4',
    'webm' => 'audio/webm'
];
$expectedMime = isset($mimeMap[$fileExt]) ? $mimeMap[$fileExt] : 'unknown';

echo "Expected MIME Type: $expectedMime\n";

if ($mimeType === $expectedMime || in_array($mimeType, ALLOWED_MIME_TYPES)) {
    echo "✓ MIME type is valid\n";
} else {
    echo "⚠ WARNING: MIME type mismatch (will use extension-based detection)\n";
}

echo "\n================================\n";
if ($isValidAudio) {
    echo "✓ File appears to be valid for upload\n";
    echo "\nYou can try uploading this file through the web interface.\n";
} else {
    echo "⚠ WARNING: File may not be recognized by AssemblyAI\n";
    echo "\nRecommendations:\n";
    echo "1. Verify the file plays correctly in a media player\n";
    echo "2. Try converting to a different format (e.g., .mp3 or .wav)\n";
    echo "3. Check if the file is corrupted\n";
}

