<?php
// admin.php
require_once 'core/Auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user = $auth->getCurrentUser();
if ($user['role'] !== 'admin') {
    die('Unauthorized: Admin access required.');
}

// Fetch stats
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_files' => $pdo->query("SELECT COUNT(*) FROM files")->fetchColumn(),
    'total_storage' => $pdo->query("SELECT SUM(size) FROM files")->fetchColumn() ?: 0,
];

// Fetch recent users
$users = $pdo->query("SELECT id, name, email, role, quota_limit_bytes, quota_used_bytes, status, created_at FROM users ORDER BY created_at DESC LIMIT 50")->fetchAll();

require_once 'includes/header.php';
?>

<div class="flex flex-col h-screen bg-gray-50 dark:bg-gray-900">
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center z-10">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center">
            <i class="fa-solid fa-shield-halved text-purple-600 mr-3"></i> Admin Dashboard
        </h1>
        <div class="flex gap-4 items-center">
            <a href="dashboard.php" class="text-blue-600 dark:text-blue-400 hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i> Back to App</a>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex items-center">
                <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex justify-center items-center text-2xl mr-4">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total Users</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= number_format($stats['total_users']) ?></p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex items-center">
                <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 flex justify-center items-center text-2xl mr-4">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total Files</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= number_format($stats['total_files']) ?></p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex items-center">
                <div class="w-14 h-14 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 flex justify-center items-center text-2xl mr-4">
                    <i class="fa-solid fa-server"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Storage Used</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= formatBytes($stats['total_storage']) ?></p>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Recent Users</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-sm border-b border-gray-200 dark:border-gray-700">
                            <th class="px-6 py-3 font-medium">Name</th>
                            <th class="px-6 py-3 font-medium">Email</th>
                            <th class="px-6 py-3 font-medium">Role</th>
                            <th class="px-6 py-3 font-medium">Storage</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach($users as $u): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100"><?= htmlspecialchars($u['name']) ?></td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                                    <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 
                                       ($u['role'] === 'premium' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300') ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                <?= formatBytes($u['quota_used_bytes']) ?> / <span class="text-xs"><?= formatBytes($u['quota_limit_bytes'], 0) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $u['status'] === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-blue-600 dark:text-blue-400 hover:underline text-xs font-medium mr-2">Edit</button>
                                <?php if($u['id'] !== $user['id']): ?>
                                 <button class="text-red-600 dark:text-red-400 hover:underline text-xs font-medium">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
