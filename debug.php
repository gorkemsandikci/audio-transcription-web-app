<?php
/**
 * Debug endpoint to check API connectivity and configuration
 * Access: http://localhost:8000/debug.php
 */

// Start output buffering to prevent header issues
ob_start();

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug - Audio Transcription App</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Audio Transcription App - Debug</h1>
    
    <div class="section">
        <h2>Configuration</h2>
        <p><strong>DEV_MODE:</strong> <?php echo defined('DEV_MODE') && DEV_MODE ? '<span class="success">Enabled</span>' : '<span class="warning">Disabled</span>'; ?></p>
        <p><strong>MAX_FILE_SIZE:</strong> <?php echo MAX_FILE_SIZE / (1024*1024); ?>MB</p>
        <p><strong>AssemblyAI API Key:</strong> <?php echo substr(ASSEMBLYAI_API_KEY, 0, 10) . '...' . (strlen(ASSEMBLYAI_API_KEY) > 20 ? substr(ASSEMBLYAI_API_KEY, -10) : ''); ?></p>
        <p><strong>Claude API Key:</strong> <?php echo substr(CLAUDE_API_KEY, 0, 10) . '...' . (strlen(CLAUDE_API_KEY) > 20 ? substr(CLAUDE_API_KEY, -10) : ''); ?></p>
    </div>
    
    <div class="section">
        <h2>PHP Settings</h2>
        <p><strong>upload_max_filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
        <p><strong>post_max_size:</strong> <?php echo ini_get('post_max_size'); ?></p>
        <p><strong>max_execution_time:</strong> <?php echo ini_get('max_execution_time'); ?> seconds</p>
        <p><strong>memory_limit:</strong> <?php echo ini_get('memory_limit'); ?></p>
    </div>
    
    <div class="section">
        <h2>API Connectivity Test</h2>
        <?php
        // Test AssemblyAI
        echo "<h3>AssemblyAI</h3>";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.assemblyai.com/v2/transcript');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['authorization: ' . ASSEMBLYAI_API_KEY]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo "<p class='error'>Connection Error: $curlError</p>";
        } elseif ($httpCode === 401) {
            echo "<p class='error'>API Key Invalid (401 Unauthorized)</p>";
        } elseif ($httpCode === 200 || $httpCode === 400) {
            echo "<p class='success'>Connection OK (HTTP $httpCode)</p>";
        } else {
            echo "<p class='warning'>Unexpected response (HTTP $httpCode)</p>";
        }
        
        // Test Claude
        echo "<h3>Anthropic Claude</h3>";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.anthropic.com/v1/messages');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . CLAUDE_API_KEY,
            'anthropic-version: 2023-06-01',
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 10,
            'messages' => [['role' => 'user', 'content' => 'test']]
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo "<p class='error'>Connection Error: $curlError</p>";
        } elseif ($httpCode === 401) {
            echo "<p class='error'>API Key Invalid (401 Unauthorized)</p>";
        } elseif ($httpCode === 200 || $httpCode === 400) {
            echo "<p class='success'>Connection OK (HTTP $httpCode)</p>";
        } else {
            echo "<p class='warning'>Unexpected response (HTTP $httpCode)</p>";
            if ($response) {
                echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Error Log (Last 50 lines)</h2>
        <?php
        $errorLog = __DIR__ . '/error.log';
        if (file_exists($errorLog)) {
            $lines = file($errorLog);
            $lastLines = array_slice($lines, -50);
            echo "<pre>" . htmlspecialchars(implode('', $lastLines)) . "</pre>";
        } else {
            echo "<p class='warning'>No error log found yet.</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Directory Permissions</h2>
        <p><strong>uploads/:</strong> <?php echo is_writable(UPLOAD_DIR) ? '<span class="success">Writable</span>' : '<span class="error">Not Writable</span>'; ?></p>
        <p><strong>rate_limit.json:</strong> <?php echo is_writable(RATE_LIMIT_FILE) ? '<span class="success">Writable</span>' : '<span class="error">Not Writable</span>'; ?></p>
    </div>
</body>
</html>

