// upload.js: Handle file input and display selected file names

document.addEventListener('DOMContentLoaded', () => {
    const elements = {
        form: document.getElementById('uploadForm'),
        fileInput: document.getElementById('fileInput'),
        status: document.getElementById('uploadStatus'),
        submit: document.getElementById('submitButton'),
        fileName: document.getElementById('fileNameDisplay')
    };

    const handleFileChange = () => {
        const file = elements.fileInput.files[0];
        if (file) {
            elements.fileName.textContent = file.name;
            elements.fileName.className = 'file-name-display active';
        }
    };

    const handleFormSubmit = async (event) => {
        event.preventDefault();
        elements.submit.disabled = true;
        elements.submit.textContent = 'Uploading...';

        try {
            const response = await fetch('/upload.php', {
                method: 'POST',
                body: new FormData(elements.form)
            });

            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            elements.status.textContent = 'Upload successful! Your cat image is pending review and will be visible once approved.';
            elements.status.className = 'upload-status success';
            elements.form.reset();
            elements.fileName.textContent = '';
            elements.fileName.className = 'file-name-display';
        } catch (error) {
            elements.status.textContent = `Upload failed: ${error.message}`;
            elements.status.className = 'upload-status error';
        } finally {
            elements.submit.disabled = false;
            elements.submit.textContent = 'Upload Image';
        }
    };

    elements.fileInput.addEventListener('change', handleFileChange);
    elements.form.addEventListener('submit', handleFormSubmit);
});