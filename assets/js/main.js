// Main JavaScript for audio transcription app

document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('audioFile');
    const browseBtn = document.getElementById('browseBtn');
    const uploadForm = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');
    const dropZoneContent = document.getElementById('dropZoneContent');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFile = document.getElementById('removeFile');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const loadingStatus = document.getElementById('loadingStatus');
    const resultsSection = document.getElementById('resultsSection');
    
    let selectedFile = null;
    
    // Browse button click
    browseBtn.addEventListener('click', () => {
        fileInput.click();
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });
    
    // Drag and drop handlers
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        
        if (e.dataTransfer.files.length > 0) {
            handleFileSelect(e.dataTransfer.files[0]);
        }
    });
    
    // Remove file
    removeFile.addEventListener('click', () => {
        selectedFile = null;
        fileInput.value = '';
        dropZoneContent.classList.remove('hidden');
        fileInfo.classList.add('hidden');
        updateSubmitButton();
    });
    
    // Handle file selection
    function handleFileSelect(file) {
        // Validate file type
        const allowedExtensions = ['m4a', 'mp3', 'wav', 'mp4', 'webm'];
        const fileExt = file.name.split('.').pop().toLowerCase();
        
        if (!allowedExtensions.includes(fileExt)) {
            showError('Please upload a valid audio file (.m4a, .mp3, .wav, .mp4, .webm)');
            return;
        }
        
        // Validate file size (25MB)
        const maxSize = 25 * 1024 * 1024;
        if (file.size > maxSize) {
            showError('File size must be under 25MB');
            return;
        }
        
        selectedFile = file;
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        dropZoneContent.classList.add('hidden');
        fileInfo.classList.remove('hidden');
        updateSubmitButton();
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Update submit button state
    function updateSubmitButton() {
        // No CAPTCHA - only check if file is selected
        submitBtn.disabled = !selectedFile;
    }
    
    // Check file selection periodically
    setInterval(() => {
        updateSubmitButton();
    }, 500);
    
    // Form submission
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!selectedFile) {
            showError('Please select a file');
            return;
        }
        
        // Show loading indicator
        loadingIndicator.classList.remove('hidden');
        resultsSection.classList.add('hidden');
        submitBtn.disabled = true;
        loadingStatus.textContent = 'Uploading file...';
        
        // Create form data
        const formData = new FormData();
        formData.append('audioFile', selectedFile);
        
        try {
            loadingStatus.textContent = 'Uploading to transcription service...';
            
            // For large files, show progress
            const fileSizeMB = (selectedFile.size / (1024 * 1024)).toFixed(1);
            if (fileSizeMB > 50) {
                loadingStatus.textContent = `Uploading large file (${fileSizeMB}MB) - This may take a few minutes...`;
            }
            
            const response = await fetch('process.php', {
                method: 'POST',
                body: formData
            });
            
            // Get response text first to check if it's valid JSON
            const responseText = await response.text();
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                // Not valid JSON - might be HTML error page or plain text
                console.error('Invalid JSON response:', responseText.substring(0, 500));
                throw new Error('Server returned invalid response. Please check the error log or try again.');
            }
            
            if (!response.ok) {
                throw new Error(data.error || `Server error (${response.status})`);
            }
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (!data.success) {
                throw new Error('Processing failed. Please try again.');
            }
            
            loadingStatus.textContent = 'Processing transcription (this may take 10-30 minutes for long files)...';
            
            // Wait a bit for transcription processing
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            loadingStatus.textContent = 'Generating summary...';
            
            // Display results
            document.getElementById('englishResults').innerHTML = data.englishSummary;
            document.getElementById('turkishResults').innerHTML = data.turkishSummary;
            document.getElementById('transcriptionResults').textContent = data.transcription;
            
            loadingIndicator.classList.add('hidden');
            resultsSection.classList.remove('hidden');
            
            // Scroll to results
            resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Reset form
            selectedFile = null;
            fileInput.value = '';
            dropZoneContent.classList.remove('hidden');
            fileInfo.classList.add('hidden');
            updateSubmitButton();
            
        } catch (error) {
            loadingIndicator.classList.add('hidden');
            showError(error.message || 'An error occurred while processing your file');
            submitBtn.disabled = false;
        }
    });
});

// Copy to clipboard function
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent || element.innerText;
    
    navigator.clipboard.writeText(text).then(() => {
        // Show temporary success message
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('bg-green-100', 'text-green-700');
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-100', 'text-green-700');
        }, 2000);
    }).catch(err => {
        showError('Failed to copy to clipboard');
    });
}

// Download text function
function downloadText(elementId, filename) {
    const element = document.getElementById(elementId);
    const text = element.textContent || element.innerText;
    
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Show error toast
function showError(message) {
    const errorToast = document.getElementById('errorToast');
    const errorMessage = document.getElementById('errorMessage');
    errorMessage.textContent = message;
    errorToast.classList.remove('hidden');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        closeErrorToast();
    }, 5000);
}

// Close error toast
function closeErrorToast() {
    const errorToast = document.getElementById('errorToast');
    errorToast.classList.add('hidden');
}

