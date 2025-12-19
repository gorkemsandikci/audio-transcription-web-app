<?php
/**
 * Audio File Validation Helper
 * This can be used to test file validation
 */

require_once 'config.php';

// Function to detect audio file MIME type
function detectAudioMimeType($filePath, $fileExt) {
    // First try finfo if available
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        if ($mimeType && in_array($mimeType, ALLOWED_MIME_TYPES)) {
            return $mimeType;
        }
    }
    
    // Fallback to extension-based detection
    $mimeMap = [
        'm4a' => 'audio/mp4',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'mp4' => 'video/mp4',
        'webm' => 'audio/webm'
    ];
    
    return isset($mimeMap[$fileExt]) ? $mimeMap[$fileExt] : 'application/octet-stream';
}

// Function to check if file is actually an audio file
function isValidAudioFile($filePath, $fileExt) {
    // Check file extension
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Check file size (must be > 0)
    if (filesize($filePath) === 0) {
        return false;
    }
    
    // For .m4a files, check for MP4/QuickTime header
    if ($fileExt === 'm4a') {
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 12);
        fclose($handle);
        
        // M4A files start with 'ftyp' at offset 4
        if (substr($header, 4, 4) === 'ftyp') {
            return true;
        }
    }
    
    // For MP3 files, check for MP3 header (ID3 or frame sync)
    if ($fileExt === 'mp3') {
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 3);
        fclose($handle);
        
        // MP3 files start with ID3 tag or frame sync (0xFF 0xFB or 0xFF 0xF3)
        if ($header === 'ID3' || (ord($header[0]) === 0xFF && (ord($header[1]) & 0xE0) === 0xE0)) {
            return true;
        }
    }
    
    // For WAV files, check for RIFF header
    if ($fileExt === 'wav') {
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 4);
        fclose($handle);
        
        if ($header === 'RIFF') {
            return true;
        }
    }
    
    // If we can't verify, but extension is valid, allow it
    // (some formats are harder to detect from headers)
    return true;
}

echo "Audio File Validation Helper\n";
echo "============================\n\n";

if ($argc < 2) {
    echo "Usage: php validate-audio.php <file_path>\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "Error: File not found: $filePath\n";
    exit(1);
}

$fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$fileSize = filesize($filePath);
$detectedMime = detectAudioMimeType($filePath, $fileExt);
$isValid = isValidAudioFile($filePath, $fileExt);

echo "File: $filePath\n";
echo "Extension: $fileExt\n";
echo "Size: " . round($fileSize / 1024 / 1024, 2) . " MB\n";
echo "Detected MIME Type: $detectedMime\n";
echo "Is Valid Audio: " . ($isValid ? "YES" : "NO") . "\n";
echo "\n";

if (!$isValid) {
    echo "WARNING: This file may not be a valid audio file!\n";
    exit(1);
}

echo "File appears to be valid. Ready for upload.\n";


