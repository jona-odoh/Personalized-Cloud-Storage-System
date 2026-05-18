// assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {

    // --- Modal Logic ---
    const newBtn = document.getElementById('new-btn');
    const folderModal = document.getElementById('create-folder-modal');
    const folderContent = document.getElementById('create-folder-content');
    const cancelFolderBtn = document.getElementById('cancel-folder-btn');
    const saveFolderBtn = document.getElementById('save-folder-btn');
    const newFolderName = document.getElementById('new-folder-name');

    if (newBtn && folderModal) {
        newBtn.addEventListener('click', () => {
            folderModal.classList.remove('hidden');
            setTimeout(() => {
                folderContent.classList.remove('scale-95', 'opacity-0');
                folderContent.classList.add('scale-100', 'opacity-100');
                newFolderName.focus();
            }, 10);
        });

        const closeModal = () => {
            folderContent.classList.remove('scale-100', 'opacity-100');
            folderContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                folderModal.classList.add('hidden');
                newFolderName.value = '';
            }, 300);
        };

        cancelFolderBtn.addEventListener('click', closeModal);
        folderModal.addEventListener('click', (e) => {
            if (e.target === folderModal) closeModal();
        });

        saveFolderBtn.addEventListener('click', async () => {
            const name = newFolderName.value.trim();
            if(!name) return;

            const urlParams = new URLSearchParams(window.location.search);
            const parentId = urlParams.get('folder');

            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('name', name);
            if(parentId) formData.append('parent_id', parentId);

            try {
                saveFolderBtn.disabled = true;
                saveFolderBtn.textContent = 'Creating...';
                
                const response = await fetch('api/folder_actions.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if(data.status) window.location.reload();
                else alert(data.message);
            } catch (err) {
                alert('An error occurred.');
            } finally {
                saveFolderBtn.disabled = false;
                saveFolderBtn.textContent = 'Create';
                closeModal();
            }
        });
    }

    // --- Context Menu Logic ---
    const body = document.body;
    let contextMenu = null;

    function buildContextMenu() {
        if(contextMenu) contextMenu.remove();
        
        contextMenu = document.createElement('div');
        contextMenu.className = 'fixed bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl rounded-lg py-2 w-48 z-50 text-sm';
        contextMenu.style.display = 'none';
        body.appendChild(contextMenu);

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (contextMenu.style.display === 'block' && e.button !== 2) {
                contextMenu.style.display = 'none';
            }
        });
    }

    function showContextMenu(x, y, items) {
        if(!contextMenu) buildContextMenu();
        
        contextMenu.innerHTML = '';
        items.forEach(item => {
            if(item.type === 'divider') {
                const hr = document.createElement('hr');
                hr.className = 'my-1 border-gray-200 dark:border-gray-700';
                contextMenu.appendChild(hr);
                return;
            }

            const btn = document.createElement('button');
            btn.className = `w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors ${item.colorClass || 'text-gray-700 dark:text-gray-300'}`;
            btn.innerHTML = `<i class="fa-solid fa-${item.icon} w-4 text-center"></i> ${item.label}`;
            
            btn.addEventListener('click', (e) => {
                contextMenu.style.display = 'none';
                if(item.action) item.action(e);
            });
            contextMenu.appendChild(btn);
        });

        contextMenu.style.display = 'block';
        
        // Boundaries
        const rect = contextMenu.getBoundingClientRect();
        if(x + rect.width > window.innerWidth) x -= rect.width;
        if(y + rect.height > window.innerHeight) y -= rect.height;

        contextMenu.style.left = `${x}px`;
        contextMenu.style.top = `${y}px`;
    }

    const fileItems = document.querySelectorAll('.file-item');
    const folderItems = document.querySelectorAll('.folder-item');

    fileItems.forEach(item => {
        item.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            const id = item.getAttribute('data-id');
            const name = item.getAttribute('data-name');
            
            showContextMenu(e.clientX, e.clientY, [
                { icon: 'download', label: 'Download', action: () => window.location.href = `api/download.php?id=${id}` },
                { icon: 'star', label: 'Toggle Star', action: () => toggleStar(id) },
                { type: 'divider' },
                { icon: 'pen', label: 'Rename', action: () => promptRenameFile(id, name) },
                { icon: 'trash', label: 'Move to Trash', colorClass: 'text-orange-500', action: () => trashFile(id) },
                { icon: 'trash-can', label: 'Delete Permanently', colorClass: 'text-red-600', action: () => deleteFilePermanently(id) }
            ]);
        });
    });

    folderItems.forEach(item => {
        item.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            const id = item.getAttribute('data-id');
            const currentName = item.querySelector('span').textContent.trim();

            showContextMenu(e.clientX, e.clientY, [
                { icon: 'folder-open', label: 'Open', action: () => window.location.href = `dashboard.php?folder=${id}` },
                { type: 'divider' },
                { icon: 'pen', label: 'Rename', action: () => promptRenameFolder(id, currentName) },
                { icon: 'trash-can', label: 'Delete Folder', colorClass: 'text-red-600', action: () => deleteFolder(id) }
            ]);
        });
    });

    // Helper functions for context menu actions
    async function promptRenameFile(id, oldName) {
        const newName = prompt('Enter new file name:', oldName);
        if(newName && newName !== oldName) {
            let formData = new FormData();
            formData.append('action', 'rename');
            formData.append('id', id);
            formData.append('name', newName);
            await fetch('api/file_actions.php', { method: 'POST', body: formData});
            window.location.reload();
        }
    }

    async function promptRenameFolder(id, oldName) {
        const newName = prompt('Enter new folder name:', oldName);
        if(newName && newName !== oldName) {
            let formData = new FormData();
            formData.append('action', 'rename');
            formData.append('id', id);
            formData.append('name', newName);
            await fetch('api/folder_actions.php', { method: 'POST', body: formData});
            window.location.reload();
        }
    }
    
    async function toggleStar(id) {
        let formData = new FormData();
        formData.append('action', 'star');
        formData.append('id', id);
        await fetch('api/file_actions.php', { method: 'POST', body: formData});
        window.location.reload();
    }

    async function trashFile(id) {
        if(confirm('Are you sure you want to move this file to trash?')) {
            let formData = new FormData();
            formData.append('action', 'trash');
            formData.append('id', id);
            await fetch('api/file_actions.php', { method: 'POST', body: formData});
            window.location.reload();
        }
    }

    async function deleteFilePermanently(id) {
        if(confirm('WARNING: Are you sure you want to PERMANENTLY delete this file? This action cannot be undone.')) {
            let formData = new FormData();
            formData.append('action', 'delete_permanently');
            formData.append('id', id);
            await fetch('api/file_actions.php', { method: 'POST', body: formData});
            window.location.reload();
        }
    }

    async function deleteFolder(id) {
        if(confirm('WARNING: Are you sure you want to PERMANENTLY delete this folder? All subfolders and files inside it will also be permanently deleted! This action cannot be undone.')) {
            let formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            await fetch('api/folder_actions.php', { method: 'POST', body: formData});
            window.location.reload();
        }
    }
});
