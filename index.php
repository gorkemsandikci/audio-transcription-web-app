<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Transcription & Summary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <header class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Audio Transcription & Summary</h1>
            <p class="text-gray-600">Upload an audio file to get transcription and AI-powered summary</p>
        </header>

        <!-- Upload Card -->
        <div class="bg-white rounded-lg shadow-md p-8 mb-6">
            <form id="uploadForm" enctype="multipart/form-data">
                <!-- Drag and Drop Zone -->
                <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center cursor-pointer transition-colors hover:border-indigo-500 mb-6">
                    <input type="file" id="audioFile" name="audioFile" accept=".m4a,.mp3,.wav,.mp4,.webm" class="hidden" required>
                    <div id="dropZoneContent">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-lg text-gray-700 mb-2">Drag and drop your audio file here</p>
                        <p class="text-sm text-gray-500 mb-4">or</p>
                        <button type="button" id="browseBtn" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                            Browse Files
                        </button>
                        <p class="text-xs text-gray-400 mt-4">Supported formats: .m4a, .mp3, .wav, .mp4, .webm (Max 25MB)</p>
                    </div>
                    <div id="fileInfo" class="hidden">
                        <svg class="mx-auto h-12 w-12 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p id="fileName" class="text-lg font-semibold text-gray-800 mb-2"></p>
                        <p id="fileSize" class="text-sm text-gray-500 mb-4"></p>
                        <button type="button" id="removeFile" class="text-red-600 hover:text-red-700 text-sm">Remove file</button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                    Process Audio
                </button>
            </form>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="hidden bg-white rounded-lg shadow-md p-8 mb-6">
            <div class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mr-4"></div>
                <div>
                    <p class="text-lg font-semibold text-gray-800">Processing your audio...</p>
                    <p id="loadingStatus" class="text-sm text-gray-600">Uploading file...</p>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div id="resultsSection" class="hidden">
            <!-- English Results -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">English Summary</h2>
                    <div class="flex gap-2">
                        <button onclick="copyToClipboard('englishResults')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            Copy
                        </button>
                        <button onclick="downloadText('englishResults', 'summary-english.txt')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            Download
                        </button>
                    </div>
                </div>
                <div id="englishResults" class="prose max-w-none"></div>
            </div>

            <!-- Turkish Results -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Turkish Summary (Türkçe)</h2>
                    <div class="flex gap-2">
                        <button onclick="copyToClipboard('turkishResults')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            Copy
                        </button>
                        <button onclick="downloadText('turkishResults', 'summary-turkish.txt')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            Download
                        </button>
                    </div>
                </div>
                <div id="turkishResults" class="prose max-w-none"></div>
            </div>

            <!-- Full Transcription -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Full Transcription</h2>
                    <div class="flex gap-2">
                        <button onclick="copyToClipboard('transcriptionResults')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            Copy
                        </button>
                        <button onclick="downloadText('transcriptionResults', 'transcription.txt')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                            Download
                        </button>
                    </div>
                </div>
                <div id="transcriptionResults" class="prose max-w-none whitespace-pre-wrap text-gray-700"></div>
            </div>
        </div>

        <!-- Error Toast -->
        <div id="errorToast" class="hidden fixed bottom-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg max-w-md z-50">
            <div class="flex items-center justify-between">
                <p id="errorMessage" class="mr-4"></p>
                <button onclick="closeErrorToast()" class="text-white hover:text-gray-200">×</button>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>

