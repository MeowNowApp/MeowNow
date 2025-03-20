// upload.js: Handle file input and display selected file names

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const submitButton = uploadForm.querySelector('button[type="submit"]');
    const fileInput = document.getElementById('imageFile');
    const fileNameDisplay = document.getElementById('file-name-display');

    // Handle file selection
    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (file) {
                // Check if file is JPG
                if (!file.type.match('image/jpeg')) {
                    uploadStatus.textContent = 'Please select a JPG file only.';
                    uploadStatus.className = 'upload-status error';
                    fileInput.value = ''; // Clear the file input
                    fileNameDisplay.textContent = '';
                    fileNameDisplay.className = 'file-name-display';
                    return;
                }

                // Check file size (50MB = 50 * 1024 * 1024 bytes)
                if (file.size > 50 * 1024 * 1024) {
                    uploadStatus.textContent = 'File is too large. Maximum size is 50MB.';
                    uploadStatus.className = 'upload-status error';
                    fileInput.value = ''; // Clear the file input
                    fileNameDisplay.textContent = '';
                    fileNameDisplay.className = 'file-name-display';
                    return;
                }
                
                fileNameDisplay.textContent = file.name;
                fileNameDisplay.className = 'file-name-display selected';
                uploadStatus.textContent = '';
                uploadStatus.className = 'upload-status';
            } else {
                fileNameDisplay.textContent = '';
                fileNameDisplay.className = 'file-name-display';
            }
        });
    }

    // Handle form submission
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const file = fileInput.files[0];
        if (!file) {
            uploadStatus.textContent = 'Please select a file to upload.';
            uploadStatus.className = 'upload-status error';
            return;
        }

        // Double-check file type before upload
        if (!file.type.match('image/jpeg')) {
            uploadStatus.textContent = 'Please select a JPG file only.';
            uploadStatus.className = 'upload-status error';
            return;
        }

        // Double-check file size before upload
        if (file.size > 50 * 1024 * 1024) {
            uploadStatus.textContent = 'File is too large. Maximum size is 50MB.';
            uploadStatus.className = 'upload-status error';
            return;
        }
        
        // Disable submit button and show loading state
        submitButton.disabled = true;
        submitButton.textContent = 'Uploading...';
        uploadStatus.textContent = 'Uploading your cat image...';
        uploadStatus.className = 'upload-status uploading';

        try {
            const formData = new FormData(uploadForm);
            
            const response = await fetch('/upload.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                uploadStatus.textContent = 'Upload successful! Your cat image has been added to the collection.';
                uploadStatus.className = 'upload-status success';
                uploadForm.reset();
                fileNameDisplay.textContent = '';
                fileNameDisplay.className = 'file-name-display';
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            uploadStatus.textContent = `Upload failed: ${error.message}`;
            uploadStatus.className = 'upload-status error';
        } finally {
            // Re-enable submit button
            submitButton.disabled = false;
            submitButton.textContent = 'Upload Image';
        }
    });
});