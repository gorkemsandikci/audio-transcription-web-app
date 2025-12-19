<?php
/**
 * Test script to list available Gemini models
 */

require_once 'config.php';

echo "Testing Gemini API Models\n";
echo "========================\n\n";

$apiKey = GEMINI_API_KEY;

// Try to list available models
$ch = curl_init();
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey;

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Available Models:\n";
    if (isset($data['models'])) {
        foreach ($data['models'] as $model) {
            echo "- " . $model['name'] . "\n";
            if (isset($model['supportedGenerationMethods'])) {
                echo "  Supported methods: " . implode(', ', $model['supportedGenerationMethods']) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "Error (HTTP $httpCode):\n";
    echo $response . "\n";
}

// Test different model names
echo "\n\nTesting Model Names:\n";
echo "===================\n\n";

$modelsToTest = [
    'gemini-pro',
    'gemini-1.5-pro',
    'gemini-1.5-flash',
    'gemini-1.5-pro-latest',
    'gemini-1.5-flash-latest',
    'models/gemini-pro',
    'models/gemini-1.5-pro',
    'models/gemini-1.5-flash'
];

foreach ($modelsToTest as $model) {
    $ch = curl_init();
    $url = 'https://generativelanguage.googleapis.com/v1beta/' . $model . ':generateContent?key=' . $apiKey;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['content-type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'contents' => [['parts' => [['text' => 'test']]]]
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✓ $model - WORKS!\n";
        break;
    } else {
        $error = json_decode($response, true);
        $msg = isset($error['error']['message']) ? $error['error']['message'] : 'Unknown error';
        echo "✗ $model - $msg\n";
    }
}


