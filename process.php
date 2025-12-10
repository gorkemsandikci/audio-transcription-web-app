<?php
require_once 'config.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Set JSON header
header('Content-Type: application/json');

// Function to send JSON error response
function sendJsonError($message, $code = 500, $details = null) {
    http_response_code($code);
    $response = ['error' => $message];
    if ($details !== null) {
        $response['details'] = $details;
    }
    echo json_encode($response);
    exit;
}

// Rate limiting check
function checkRateLimit($ip) {
    $rateLimitData = json_decode(file_get_contents(RATE_LIMIT_FILE), true);
    $currentTime = time();
    
    // Clean old entries (older than 1 hour)
    $rateLimitData = array_filter($rateLimitData, function($entry) use ($currentTime) {
        return ($currentTime - $entry['timestamp']) < 3600;
    });
    
    // Count requests from this IP in the last hour
    $ipRequests = array_filter($rateLimitData, function($entry) use ($ip) {
        return $entry['ip'] === $ip;
    });
    
    if (count($ipRequests) >= MAX_REQUESTS_PER_HOUR) {
        return false;
    }
    
    // Add current request
    $rateLimitData[] = [
        'ip' => $ip,
        'timestamp' => $currentTime
    ];
    
    file_put_contents(RATE_LIMIT_FILE, json_encode(array_values($rateLimitData)));
    return true;
}

// Get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// CAPTCHA removed - Basic Auth provides sufficient security

// Upload file to AssemblyAI
function uploadToAssemblyAI($filePath, $mimeType = null) {
    // Determine MIME type from file extension if not provided
    if (!$mimeType) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'm4a' => 'audio/mp4',      // M4A files use audio/mp4
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'audio/mp4',      // MP4 audio files should use audio/mp4, not video/mp4
            'webm' => 'audio/webm'
        ];
        $mimeType = isset($mimeMap[$ext]) ? $mimeMap[$ext] : 'application/octet-stream';
    }
    
    // Verify file exists and is readable
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return ['error' => 'File not found or not readable'];
    }
    
    // Check file size
    $fileSize = filesize($filePath);
    if ($fileSize === 0) {
        return ['error' => 'File is empty'];
    }
    
    // Verify it's actually an audio file by checking file headers
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        return ['error' => 'Cannot open file for reading'];
    }
    
    $header = fread($handle, 12);
    fclose($handle);
    
    // Basic audio file validation (more lenient for MP4/M4A)
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $isValidAudio = false;
    
    if ($ext === 'm4a' || $ext === 'mp4') {
        // M4A/MP4 files start with 'ftyp' at offset 4, or can have various box types
        // Some MP4 files may have different headers, so we're more lenient
        $isValidAudio = (substr($header, 4, 4) === 'ftyp') || 
                       (substr($header, 0, 4) === 'ftyp') ||
                       (ord($header[0]) > 0 && ord($header[0]) < 255); // Basic sanity check
    } elseif ($ext === 'mp3') {
        // MP3 files start with ID3 tag or frame sync
        $isValidAudio = (substr($header, 0, 3) === 'ID3' || 
                        (ord($header[0]) === 0xFF && (ord($header[1]) & 0xE0) === 0xE0));
    } elseif ($ext === 'wav') {
        // WAV files start with 'RIFF'
        $isValidAudio = (substr($header, 0, 4) === 'RIFF');
    } elseif ($ext === 'webm') {
        // WebM files start with EBML header
        $isValidAudio = (substr($header, 0, 4) === "\x1a\x45\xdf\xa3");
    } else {
        // For unknown extensions, assume valid if file exists and has content
        $isValidAudio = true;
    }
    
    // Only reject if we're very sure it's invalid (for MP4/M4A, be more lenient)
    if (!$isValidAudio && ($ext !== 'm4a' && $ext !== 'mp4')) {
        error_log("Invalid audio file header for: $filePath (ext: $ext)");
        return ['error' => 'File does not appear to be a valid audio file. Please check the file format.'];
    }
    
    // For MP4/M4A files, log a warning but don't reject (AssemblyAI will validate)
    if (!$isValidAudio && ($ext === 'm4a' || $ext === 'mp4')) {
        error_log("Warning: MP4/M4A file header validation failed, but proceeding (file: $filePath)");
    }
    
    $ch = curl_init();
    
    // Use CURLFile with proper MIME type - AssemblyAI prefers explicit MIME types
    $cfile = new CURLFile($filePath, $mimeType, basename($filePath));
    
    curl_setopt($ch, CURLOPT_URL, ASSEMBLYAI_UPLOAD_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'authorization: ' . ASSEMBLYAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file' => $cfile
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 minutes for large files
    curl_setopt($ch, CURLOPT_VERBOSE, false); // Set to true for debugging
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = 'Failed to upload file to transcription service';
        if (isset($errorData['error'])) {
            $errorMsg .= ': ' . $errorData['error'];
        }
        if ($curlError) {
            $errorMsg .= ' (cURL: ' . $curlError . ')';
        }
        // Log full response for debugging
        error_log("AssemblyAI upload error: HTTP $httpCode");
        error_log("Response: " . substr($response, 0, 1000));
        
        if (isset($errorData['error'])) {
            $errorMsg .= ': ' . (is_string($errorData['error']) ? $errorData['error'] : json_encode($errorData['error']));
        } elseif (is_string($errorData)) {
            $errorMsg .= ': ' . $errorData;
        }
        
        if ($curlError) {
            $errorMsg .= ' (cURL: ' . $curlError . ')';
        }
        
        error_log("AssemblyAI upload error: $errorMsg");
        return ['error' => $errorMsg, 'http_code' => $httpCode, 'response' => substr($response, 0, 500)];
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('AssemblyAI JSON decode error: ' . json_last_error_msg());
        return ['error' => 'Invalid response from transcription service'];
    }
    
    return $data;
}

// Submit transcription job
function submitTranscription($uploadUrl) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, ASSEMBLYAI_TRANSCRIPT_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'authorization: ' . ASSEMBLYAI_API_KEY,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'audio_url' => $uploadUrl
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = 'Failed to submit transcription job';
        if (isset($errorData['error'])) {
            $errorMsg .= ': ' . $errorData['error'];
        }
        if ($curlError) {
            $errorMsg .= ' (cURL: ' . $curlError . ')';
        }
        error_log("AssemblyAI submit error: HTTP $httpCode - $errorMsg");
        return ['error' => $errorMsg];
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('AssemblyAI JSON decode error: ' . json_last_error_msg());
        return ['error' => 'Invalid response from transcription service'];
    }
    
    return $data;
}

// Get transcription result
function getTranscription($transcriptId) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, ASSEMBLYAI_TRANSCRIPT_URL . '/' . $transcriptId);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'authorization: ' . ASSEMBLYAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = 'Failed to get transcription';
        if (isset($errorData['error'])) {
            $errorMsg .= ': ' . $errorData['error'];
        }
        if ($curlError) {
            $errorMsg .= ' (cURL: ' . $curlError . ')';
        }
        error_log("AssemblyAI get transcription error: HTTP $httpCode - $errorMsg");
        return ['error' => $errorMsg];
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('AssemblyAI JSON decode error: ' . json_last_error_msg());
        return ['error' => 'Invalid response from transcription service'];
    }
    
    return $data;
}

// Get summary prompt (shared across all AI services)
function getSummaryPrompt($transcription) {
    $prompt = "You are an expert at analyzing transcriptions and creating structured summaries.\n\n";
    $prompt .= "Given the following audio transcription, please:\n\n";
    $prompt .= "1. Identify and extract the main topics discussed (maximum 5-7 main topics)\n";
    $prompt .= "2. Create a concise summary for each main topic\n";
    $prompt .= "3. Extract any action items, decisions, or key takeaways\n";
    $prompt .= "4. Note any important names, dates, or specific details mentioned\n\n";
    $prompt .= "Please provide the output in TWO languages:\n\n";
    $prompt .= "**ENGLISH VERSION:**\n";
    $prompt .= "- Main Topics: (as bullet points with brief summaries)\n";
    $prompt .= "- Key Takeaways: (important points as bullets)\n";
    $prompt .= "- Action Items: (if any mentioned)\n";
    $prompt .= "- Notable Details: (names, dates, numbers, etc.)\n\n";
    $prompt .= "**TURKISH VERSION (TÜRKÇE):**\n";
    $prompt .= "- Ana Başlıklar: (madde madde kısa özetlerle)\n";
    $prompt .= "- Önemli Noktalar: (önemli çıkarımlar madde halinde)\n";
    $prompt .= "- Aksiyon Maddeleri: (varsa)\n";
    $prompt .= "- Dikkat Çeken Detaylar: (isimler, tarihler, sayılar vb.)\n\n";
    $prompt .= "Transcription:\n" . $transcription . "\n\n";
    $prompt .= "Please format your response with clear headers and bullet points for easy reading. Be concise but comprehensive.";
    return $prompt;
}

// Get summary from Google Gemini (FREE - 60 requests/day)
function getSummaryFromGemini($transcription) {
    $prompt = getSummaryPrompt($transcription);
    
    $ch = curl_init();
    
    // Use the correct Gemini API endpoint with model name
    // Available models: gemini-2.5-flash (fast, free), gemini-2.5-pro (better quality)
    // Model names must include 'models/' prefix
    $model = 'models/gemini-2.5-flash'; // Fast and free, or 'models/gemini-2.5-pro' for better quality
    $url = 'https://generativelanguage.googleapis.com/v1beta/' . $model . ':generateContent?key=' . GEMINI_API_KEY;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = 'Failed to get summary from Gemini API';
        if (isset($errorData['error']['message'])) {
            $errorMsg .= ': ' . $errorData['error']['message'];
        } elseif (isset($errorData['error'])) {
            $errorMsg .= ': ' . (is_string($errorData['error']) ? $errorData['error'] : json_encode($errorData['error']));
        }
        if ($curlError) {
            $errorMsg .= ' (cURL: ' . $curlError . ')';
        }
        error_log("Gemini API error: HTTP $httpCode - $errorMsg");
        error_log("Gemini API response: " . substr($response, 0, 500));
        return ['error' => $errorMsg, 'http_code' => $httpCode, 'response' => substr($response, 0, 500)];
    }
    
    $data = json_decode($response, true);
    
    // Extract text from Gemini response
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return ['content' => [['text' => $data['candidates'][0]['content']['parts'][0]['text']]]];
    }
    
    // Log response for debugging
    error_log("Gemini API response structure: " . json_encode(array_keys($data)));
    
    return ['error' => 'Invalid response from Gemini API. Response: ' . substr(json_encode($data), 0, 200)];
}

// Get summary from OpenAI (FREE tier: $5 credit)
function getSummaryFromOpenAI($transcription) {
    $prompt = getSummaryPrompt($transcription);
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, OPENAI_API_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'authorization: Bearer ' . OPENAI_API_KEY,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 2000
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = 'Failed to get summary from OpenAI API';
        if (isset($errorData['error']['message'])) {
            $errorMsg .= ': ' . $errorData['error']['message'];
        }
        if ($curlError) {
            $errorMsg .= ' (cURL: ' . $curlError . ')';
        }
        error_log("OpenAI API error: HTTP $httpCode - $errorMsg");
        return ['error' => $errorMsg, 'http_code' => $httpCode];
    }
    
    $data = json_decode($response, true);
    
    // Extract text from OpenAI response
    if (isset($data['choices'][0]['message']['content'])) {
        return ['content' => [['text' => $data['choices'][0]['message']['content']]]];
    }
    
    return ['error' => 'Invalid response from OpenAI API'];
}

// Get summary from Claude (PAID)
function getSummaryFromClaude($transcription) {
    $prompt = getSummaryPrompt($transcription);
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, CLAUDE_API_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . CLAUDE_API_KEY,
        'anthropic-version: 2023-06-01',
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'claude-3-5-sonnet-20241022',
        'max_tokens' => 4096,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ]
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'Failed to get summary from Claude API';
        if ($curlError) {
            $errorMsg .= ' (cURL error: ' . $curlError . ')';
        }
        error_log("Claude API error: HTTP $httpCode - $errorMsg");
        return ['error' => $errorMsg, 'http_code' => $httpCode, 'response' => $response];
    }
    
    $data = json_decode($response, true);
    return $data;
}

// Get summary - routes to selected AI service
function getSummary($transcription) {
    $service = defined('AI_SERVICE') ? AI_SERVICE : 'gemini';
    
    switch ($service) {
        case 'gemini':
            if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'your_gemini_key_here') {
                return ['error' => 'Gemini API key not configured. Please add GEMINI_API_KEY to config.php'];
            }
            return getSummaryFromGemini($transcription);
            
        case 'openai':
            if (!defined('OPENAI_API_KEY') || OPENAI_API_KEY === 'your_openai_key_here') {
                return ['error' => 'OpenAI API key not configured. Please add OPENAI_API_KEY to config.php'];
            }
            return getSummaryFromOpenAI($transcription);
            
        case 'claude':
            if (!defined('CLAUDE_API_KEY') || CLAUDE_API_KEY === 'your_anthropic_key_here') {
                return ['error' => 'Claude API key not configured. Please add CLAUDE_API_KEY to config.php'];
            }
            return getSummaryFromClaude($transcription);
            
        default:
            return ['error' => 'Unknown AI service: ' . $service];
    }
}

// Main processing logic
try {
    // Check rate limit
    $clientIP = getClientIP();
    if (!checkRateLimit($clientIP)) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests. Please wait before uploading again.']);
        exit;
    }
    
    // CAPTCHA removed - Basic Auth is sufficient for security
    
    // Validate file upload
    if (!isset($_FILES['audioFile']) || $_FILES['audioFile']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File upload failed']);
        exit;
    }
    
    $file = $_FILES['audioFile'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileType = $file['type'];
    
    // Validate file size
    if ($fileSize > MAX_FILE_SIZE) {
        http_response_code(400);
        echo json_encode(['error' => 'File size must be under 25MB']);
        exit;
    }
    
    // Validate file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please upload a valid audio file (.m4a, .mp3, .wav, .mp4, .webm)']);
        exit;
    }
    
    // Detect actual MIME type using file extension and content
    $detectedMimeType = $fileType;
    
    // If browser reports application/octet-stream or video/mp4, try to detect from extension
    // For MP4 files, use audio/mp4 instead of video/mp4 (AssemblyAI prefers audio/mp4 for audio content)
    if ($fileType === 'application/octet-stream' || empty($fileType) || $fileType === 'video/mp4') {
        $mimeMap = [
            'm4a' => 'audio/mp4',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'audio/mp4',  // Use audio/mp4 for MP4 audio files (not video/mp4)
            'webm' => 'audio/webm'
        ];
        if (isset($mimeMap[$fileExt])) {
            $detectedMimeType = $mimeMap[$fileExt];
        }
    }
    
    // Also try to detect from file content using finfo if available
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentMimeType = finfo_file($finfo, $fileTmpName);
        finfo_close($finfo);
        
        if ($contentMimeType && in_array($contentMimeType, ALLOWED_MIME_TYPES)) {
            $detectedMimeType = $contentMimeType;
        }
    }
    
    // Validate MIME type (allow application/octet-stream if extension is valid)
    if (!in_array($detectedMimeType, ALLOWED_MIME_TYPES) && 
        !($fileType === 'application/octet-stream' && in_array($fileExt, ALLOWED_EXTENSIONS))) {
        error_log("Invalid MIME type: $fileType (detected: $detectedMimeType) for file: $fileName");
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Please ensure the file is a valid audio file.']);
        exit;
    }
    
    // Save file temporarily
    $tempFileName = uniqid('audio_', true) . '.' . $fileExt;
    $tempFilePath = UPLOAD_DIR . $tempFileName;
    
    if (!move_uploaded_file($fileTmpName, $tempFilePath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
        exit;
    }
    
    // Upload to AssemblyAI with detected MIME type
    $uploadResult = uploadToAssemblyAI($tempFilePath, $detectedMimeType);
    if (isset($uploadResult['error'])) {
        unlink($tempFilePath);
        http_response_code(500);
        echo json_encode(['error' => $uploadResult['error']]);
        exit;
    }
    
    $audioUrl = $uploadResult['upload_url'];
    
    // Submit transcription
    $transcriptResult = submitTranscription($audioUrl);
    if (isset($transcriptResult['error'])) {
        unlink($tempFilePath);
        http_response_code(500);
        echo json_encode(['error' => $transcriptResult['error']]);
        exit;
    }
    
    $transcriptId = $transcriptResult['id'];
    
    // Poll for transcription result
    // For large files (2+ hours), transcription can take 30-60 minutes
    // Increased attempts and sleep time for long audio files
    $maxAttempts = 360; // 360 attempts * 10 seconds = 60 minutes max wait time
    $attempt = 0;
    $transcription = null;
    
    while ($attempt < $maxAttempts) {
        sleep(10); // Check every 10 seconds instead of 2 (reduces API calls)
        $result = getTranscription($transcriptId);
        
        if (isset($result['error'])) {
            unlink($tempFilePath);
            http_response_code(500);
            echo json_encode(['error' => $result['error']]);
            exit;
        }
        
        if ($result['status'] === 'completed') {
            $transcription = $result['text'];
            break;
        } elseif ($result['status'] === 'error') {
            unlink($tempFilePath);
            http_response_code(500);
            echo json_encode(['error' => 'Transcription failed']);
            exit;
        }
        
        $attempt++;
    }
    
    if (!$transcription) {
        unlink($tempFilePath);
        http_response_code(500);
        echo json_encode(['error' => 'Transcription timed out']);
        exit;
    }
    
    // Get summary from Claude
    $summaryResult = getSummary($transcription);
    if (isset($summaryResult['error'])) {
        unlink($tempFilePath);
        http_response_code(500);
        echo json_encode(['error' => $summaryResult['error']]);
        exit;
    }
    
    // Validate Claude API response structure
    if (!isset($summaryResult['content']) || !is_array($summaryResult['content']) || empty($summaryResult['content'])) {
        unlink($tempFilePath);
        http_response_code(500);
        echo json_encode(['error' => 'Invalid response from AI service']);
        exit;
    }
    
    $summaryText = $summaryResult['content'][0]['text'];
    
    // Clean up temporary file
    unlink($tempFilePath);
    
    // Parse summary into English and Turkish sections
    $englishSummary = '';
    $turkishSummary = '';
    
    // Simple parsing - look for language markers
    if (preg_match('/\*\*ENGLISH VERSION:\*\*(.*?)(?=\*\*TURKISH|$)/s', $summaryText, $englishMatch)) {
        $englishSummary = trim($englishMatch[1]);
    }
    
    if (preg_match('/\*\*TURKISH VERSION.*?:\*\*(.*?)$/s', $summaryText, $turkishMatch)) {
        $turkishSummary = trim($turkishMatch[1]);
    }
    
    // If parsing failed, use the whole text
    if (empty($englishSummary) && empty($turkishSummary)) {
        $englishSummary = $summaryText;
        $turkishSummary = $summaryText;
    }
    
    // Convert markdown to HTML
    function markdownToHtml($text) {
        // Convert headers
        $text = preg_replace('/^### (.*)$/m', '<h3 class="text-xl font-bold mt-4 mb-2">$1</h3>', $text);
        $text = preg_replace('/^## (.*)$/m', '<h2 class="text-2xl font-bold mt-6 mb-3">$1</h2>', $text);
        $text = preg_replace('/^# (.*)$/m', '<h1 class="text-3xl font-bold mt-8 mb-4">$1</h1>', $text);
        
        // Convert bullet points
        $text = preg_replace('/^[-*] (.*)$/m', '<li class="ml-4 mb-1">$1</li>', $text);
        $text = preg_replace('/(<li.*<\/li>)/s', '<ul class="list-disc ml-6 mb-4">$1</ul>', $text);
        
        // Convert bold
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        
        // Convert line breaks
        $text = nl2br($text);
        
        return $text;
    }
    
    $response = [
        'success' => true,
        'transcription' => $transcription,
        'englishSummary' => markdownToHtml($englishSummary),
        'turkishSummary' => markdownToHtml($turkishSummary)
    ];
    
    // Ensure valid JSON output
    $jsonOutput = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if ($jsonOutput === false) {
        error_log('JSON encoding error: ' . json_last_error_msg());
        sendJsonError('Failed to encode response', 500);
    }
    
    echo $jsonOutput;
    
} catch (Exception $e) {
    error_log('Process error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    sendJsonError('An unexpected error occurred: ' . $e->getMessage(), 500);
} catch (Error $e) {
    error_log('Fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    sendJsonError('A fatal error occurred. Please check the error log.', 500);
}
