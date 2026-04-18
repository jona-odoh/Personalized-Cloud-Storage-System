// assets/js/uploader.js

document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.getElementById('file-dropzone');
    const toast = document.getElementById('upload-toast');
    const toastList = document.getElementById('upload-file-list');
    const closeToast = document.getElementById('close-toast');
    const statusText = document.getElementById('upload-status-text');

    if (!dropzone) return;

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight dropzone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropzone.classList.add('dragover');
    }

    function unhighlight(e) {
        dropzone.classList.remove('dragover');
    }

    // Handle dropped files
    dropzone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        handleFiles(files);
    }

    // Handle file selection via input (if we add a generic upload button later)
    // function handleFilesSelect(e) { handleFiles(this.files); }

    function handleFiles(files) {
        if(files.length === 0) return;
        
        showToast();
        ([...files]).forEach(uploadFile);
    }

    function showToast() {
        toast.classList.remove('translate-y-full', 'opacity-0');
    }

    if(closeToast) {
        closeToast.addEventListener('click', () => {
             toast.classList.add('translate-y-full', 'opacity-0');
        });
    }

    function uploadFile(file) {
        const urlParams = new URLSearchParams(window.location.search);
        const folderId = urlParams.get('folder');

        let formData = new FormData();
        formData.append('file', file);
        if(folderId) {
            formData.append('folder_id', folderId);
        }

        // Add UI element to toast
        const fileDiv = document.createElement('div');
        fileDiv.className = 'flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700 last:border-0';
        
        const nameSpan = document.createElement('span');
        nameSpan.className = 'text-sm text-gray-700 dark:text-gray-300 truncate w-3/4';
        nameSpan.textContent = file.name;

        const statusIcon = document.createElement('i');
        statusIcon.className = 'fa-solid fa-spinner fa-spin text-blue-500';

        fileDiv.appendChild(nameSpan);
        fileDiv.appendChild(statusIcon);
        toastList.prepend(fileDiv);

        fetch('api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            statusIcon.classList.remove('fa-spinner', 'fa-spin', 'text-blue-500');
            if(data.status) {
                statusIcon.classList.add('fa-check-circle', 'text-green-500');
                // Reload after small delay if successful
                setTimeout(() => window.location.reload(), 1500);
            } else {
                statusIcon.classList.add('fa-exclamation-circle', 'text-red-500');
                statusIcon.title = data.message;
                alert(`Error uploading ${file.name}: ${data.message}`);
                console.error(data.message);
            }
        })
        .catch(() => {
            statusIcon.classList.remove('fa-spinner', 'fa-spin', 'text-blue-500');
            statusIcon.classList.add('fa-exclamation-circle', 'text-red-500');
            alert(`Network error during upload of ${file.name}`);
        });
    }
});
