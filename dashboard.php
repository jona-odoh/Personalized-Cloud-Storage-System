<?php
// dashboard.php
require_once 'core/Auth.php';
require_once 'core/FolderManager.php';
require_once 'core/FileManager.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user = $auth->getCurrentUser();
$folderManager = new FolderManager($pdo);
$fileManager = new FileManager($pdo);

$currentFolderId = isset($_GET['folder']) && is_numeric($_GET['folder']) ? (int)$_GET['folder'] : null;
$view = $_GET['view'] ?? 'my_files';

$folders = [];
$files = [];
$breadcrumbs = [];
$pageTitle = 'My Files';

if ($view === 'trash') {
    $pageTitle = 'Trash';
    $stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND in_trash = TRUE ORDER BY trashed_at DESC");
    $stmt->execute([$user['id']]);
    $files = $stmt->fetchAll();
} else if ($view === 'starred') {
    $pageTitle = 'Starred';
    $stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND is_starred = TRUE AND in_trash = FALSE");
    $stmt->execute([$user['id']]);
    $files = $stmt->fetchAll();
} else if ($view === 'shared') {
    $pageTitle = 'Shared with me';
    // Dummy placeholder for Phase 5
} else {
    $folders = $folderManager->getFolders($user['id'], $currentFolderId);
    $files = $fileManager->getFiles($user['id'], $currentFolderId);
    $breadcrumbs = $currentFolderId ? $folderManager->getBreadcrumbs($user['id'], $currentFolderId) : [];
}

require_once 'includes/header.php';
?>

<!-- Topbar -->
<header class="bg-white dark:bg-gray-800 shadow-sm z-10 flex-shrink-0">
    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-4">
            <span class="text-xl font-bold text-blue-600 dark:text-blue-400 flex items-center">
                <i class="fa-solid fa-cloud mr-2"></i> <?= APP_NAME ?>
            </span>
        </div>
        
        <div class="flex items-center gap-4">
            <!-- Search Bar -->
            <div class="hidden md:block relative">
                <input type="text" placeholder="Search files..." class="pl-10 pr-4 py-2 bg-gray-100 dark:bg-gray-700 border-transparent focus:bg-white dark:focus:bg-gray-600 border focus:border-blue-500 rounded-lg text-sm transition-all focus:ring-0 text-gray-800 dark:text-gray-200 w-64">
                <i class="fa-solid fa-search absolute left-3 top-2.5 text-gray-400"></i>
            </div>
            
            <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <i class="fa-solid fa-moon text-gray-600 dark:text-gray-300"></i>
            </button>
            
            <div class="flex items-center gap-2 cursor-pointer">
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold">
                    <?= substr($user['name'], 0, 1) ?>
                </div>
                <span class="text-sm font-medium hidden sm:block"><?= htmlspecialchars($user['name']) ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-700 ml-2 text-sm"><i class="fa-solid fa-sign-out-alt"></i></a>
            </div>
        </div>
    </div>
</header>

<div class="flex flex-1 overflow-hidden h-full">
    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col hidden md:flex flex-shrink-0">
        <div class="p-4">
            <button id="new-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-plus"></i> New
            </button>
        </div>
        
        <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center px-3 py-2 <?= $view === 'my_files' ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' ?> rounded-lg transition">
                <i class="fa-solid fa-hdd w-6 text-center"></i> My Files
            </a>
            <a href="dashboard.php?view=shared" class="flex items-center px-3 py-2 <?= $view === 'shared' ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' ?>  rounded-lg transition">
                <i class="fa-solid fa-user-group w-6 text-center"></i> Shared with me
            </a>
            <a href="dashboard.php?view=starred" class="flex items-center px-3 py-2 <?= $view === 'starred' ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' ?> rounded-lg transition">
                <i class="fa-solid fa-star w-6 text-center"></i> Starred
            </a>
            <a href="dashboard.php?view=trash" class="flex items-center px-3 py-2 <?= $view === 'trash' ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' ?> rounded-lg transition pb-4 border-b border-gray-200 dark:border-gray-700 mt-2">
                <i class="fa-solid fa-trash w-6 text-center"></i> Trash
            </a>
            <?php if($user['role'] === 'admin'): ?>
            <a href="admin.php" class="flex items-center px-3 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition mt-4 font-semibold text-purple-600 dark:text-purple-400">
                <i class="fa-solid fa-shield-halved w-6 text-center"></i> Admin Panel
            </a>
            <?php endif; ?>
        </nav>
        
        <!-- Storage Quota -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 mt-auto flex-shrink-0 bg-white dark:bg-gray-800">
            <?php 
                $pct = $user['quota_limit_bytes'] > 0 ? ($user['quota_used_bytes'] / $user['quota_limit_bytes']) * 100 : 0; 
                $barColor = $pct > 90 ? 'bg-red-500' : 'bg-blue-500';
            ?>
            <div class="flex justify-between text-xs mb-1 text-gray-600 dark:text-gray-400">
                <span>Storage</span>
                <span><?= formatBytes($user['quota_used_bytes'], 0) ?> of <?= formatBytes($user['quota_limit_bytes'], 0) ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 mb-2">
                <div class="<?= $barColor ?> h-1.5 rounded-full transition-all" style="width: <?= min($pct, 100) ?>%"></div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900 overflow-hidden relative">
        <!-- Header / Breadcrumbs -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-white/50 dark:bg-gray-800/50 backdrop-blur-md sticky top-0 z-10 flex-shrink-0">
            <div class="flex items-center text-xl font-semibold text-gray-800 dark:text-gray-200">
                <a href="dashboard.php" class="hover:underline"><?= $pageTitle ?></a>
                <?php foreach($breadcrumbs as $bc): ?>
                    <i class="fa-solid fa-chevron-right text-xs mx-2 text-gray-400"></i>
                    <a href="dashboard.php?folder=<?= $bc['id'] ?>" class="hover:underline"><?= htmlspecialchars($bc['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- File View Area (Dropzone) -->
        <div id="file-dropzone" class="flex-1 overflow-y-auto p-6 dropzone" data-folder="<?= $currentFolderId ?>">
            
            <?php if (empty($folders) && empty($files)): ?>
                <div class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                    <i class="fa-regular fa-folder-open text-6xl mb-4 opacity-50 text-blue-300 dark:text-blue-900"></i>
                    <h3 class="text-xl font-medium text-gray-600 dark:text-gray-300">Nothing here yet</h3>
                    <?php if ($view === 'my_files'): ?>
                        <p class="text-sm mt-2 font-medium">Drag and drop files to upload</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Grid View -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 pb-20">
                    
                    <!-- Render Folders -->
                    <?php foreach($folders as $folder): ?>
                    <a href="dashboard.php?folder=<?= $folder['id'] ?>" class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition group cursor-pointer flex flex-col items-center select-none folder-item hover:border-blue-300 dark:hover:border-blue-700" data-id="<?= $folder['id'] ?>">
                        <i class="fa-solid fa-folder text-5xl text-blue-500 mb-2 group-hover:scale-105 transition-transform drop-shadow-sm"></i>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate w-full text-center group-hover:text-blue-600 dark:group-hover:text-blue-400">
                            <?= htmlspecialchars($folder['name']) ?>
                        </span>
                    </a>
                    <?php endforeach; ?>

                    <!-- Render Files -->
                    <?php foreach($files as $file): ?>
                    <?php 
                        $ext = strtolower($file['extension']);
                        $iconUrl = '';
                        $faClass = 'fa-file text-gray-400';
                        if (in_array($ext, ['jpg','jpeg','png','gif','svg'])) $faClass = 'fa-file-image text-purple-500';
                        elseif ($ext === 'pdf') $faClass = 'fa-file-pdf text-red-500';
                        elseif (in_array($ext, ['doc','docx'])) $faClass = 'fa-file-word text-blue-600';
                        elseif (in_array($ext, ['xls','xlsx','csv'])) $faClass = 'fa-file-excel text-green-600';
                        elseif (in_array($ext, ['zip','rar','tar','gz'])) $faClass = 'fa-file-zipper text-yellow-600';
                        elseif (in_array($ext, ['mp4','webm','mov'])) $faClass = 'fa-file-video text-pink-500';
                        elseif (in_array($ext, ['mp3','wav'])) $faClass = 'fa-file-audio text-cyan-500';
                        elseif (in_array($ext, ['html','css','js','php','py'])) $faClass = 'fa-file-code text-indigo-500';
                    ?>
                    <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition group cursor-pointer file-item relative hover:border-blue-300 dark:hover:border-blue-700 flex flex-col justify-between" data-id="<?= $file['id'] ?>" data-name="<?= htmlspecialchars($file['original_name']) ?>">
                        
                        <?php if($file['is_starred']): ?>
                        <i class="fa-solid fa-star text-yellow-400 absolute top-2 right-2 text-xs drop-shadow-sm" title="Starred"></i>
                        <?php endif; ?>

                        <div class="h-20 flex items-center justify-center mb-2 overflow-hidden mt-1">
                             <i class="fa-solid <?= $faClass ?> text-5xl group-hover:scale-105 transition-transform drop-shadow-sm"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate w-full px-1 group-hover:text-blue-600 dark:group-hover:text-blue-400" title="<?= htmlspecialchars($file['original_name']) ?>">
                                <?= htmlspecialchars($file['original_name']) ?>
                            </div>
                            <div class="text-[0.7rem] text-gray-500 w-full text-left px-1 mt-0.5">
                                <?= formatBytes($file['size'], 1) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>

        </div>
        
        <!-- Upload Toast -->
        <div id="upload-toast" class="absolute bottom-6 right-6 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 w-80 transform transition-all duration-300 translate-y-full opacity-0 z-50 overflow-hidden">
            <div class="bg-blue-50 dark:bg-gray-700/50 p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <span class="font-semibold text-sm text-blue-900 dark:text-blue-100" id="upload-status-text">Uploading files...</span>
                <button id="close-toast" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="p-2 max-h-56 overflow-y-auto custom-scrollbar" id="upload-file-list">
            </div>
        </div>

    </main>
</div>

<!-- Modal -->
<div id="create-folder-modal" class="fixed inset-0 bg-gray-900/40 dark:bg-black/60 z-50 hidden flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 w-full max-w-sm rounded-2xl shadow-2xl p-6 transform transition-all scale-95 opacity-0 duration-200" id="create-folder-content">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">New Folder</h3>
        <input type="text" id="new-folder-name" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg mb-6 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition" placeholder="Folder Name">
        <div class="flex justify-end gap-3">
            <button id="cancel-folder-btn" class="px-5 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg font-medium transition">Cancel</button>
            <button id="save-folder-btn" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-md shadow-blue-500/20 transition disabled:opacity-70">Create</button>
        </div>
    </div>
</div>

<?php 
$extraScripts = '<script src="'.BASE_URL.'/assets/js/app.js"></script><script src="'.BASE_URL.'/assets/js/uploader.js"></script>';
require_once 'includes/footer.php'; 
?>
