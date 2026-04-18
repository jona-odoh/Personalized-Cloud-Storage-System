<?php
// api/download.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../core/Auth.php';
require_once '../core/FileManager.php';

if (!$auth->isLoggedIn()) {
    die('Unauthorized');
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die('Invalid ID');

$user = $auth->getCurrentUser();
$fileManager = new FileManager($pdo);
$file = $fileManager->getFile($user['id'], $id);

if (!$file || $file['in_trash']) {
    die('File not found or is in trash.');
}

$filePath = realpath(__DIR__ . '/../storage/users/' . $user['id'] . '/' . $file['stored_name']);

if (!file_exists($filePath)) {
    die('Physical file missing.');
}

// Ensure the resolved path is within the designated user's directory (security check)
$userDir = realpath(__DIR__ . '/../storage/users/' . $user['id']);
if (strpos($filePath, $userDir) !== 0) {
    die('Access denied.');
}

// Log download activity
$stmt = $pdo->prepare("INSERT INTO activities (user_id, action, file_id, description) VALUES (?, 'download_file', ?, ?)");
$stmt->execute([$user['id'], $id, "Downloaded: {$file['original_name']}"]);

// Send headers to force download (unless it's an image we want to preview? No, this is purely a download script)
header('Content-Description: File Transfer');
header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Clear output buffer then read file to user without memory limits
ob_clean();
flush();
readfile($filePath);
exit;
