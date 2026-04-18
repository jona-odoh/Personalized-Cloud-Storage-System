<?php
// api/folder_actions.php
require_once '../core/Auth.php';
require_once '../core/FolderManager.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$folderManager = new FolderManager($pdo);

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $parentId = isset($_POST['parent_id']) && is_numeric($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if (empty($name)) {
        echo json_encode(['status' => false, 'message' => 'Folder name is required.']);
        exit;
    }

    $result = $folderManager->createFolder($user['id'], $name, $parentId);
    echo json_encode($result);
    exit;
}

if ($action === 'rename') {
     $id = (int)($_POST['id'] ?? 0);
     $name = trim($_POST['name'] ?? '');
     if(empty($name)) {
         echo json_encode(['status' => false, 'message' => 'Folder name cannot be empty.']);
         exit;
     }

     $success = $folderManager->renameFolder($user['id'], $id, $name);
     echo json_encode(['status' => $success, 'message' => $success ? 'Renamed successfully' : 'Failed to rename']);
     exit;
}

echo json_encode(['status' => false, 'message' => 'Invalid action']);
