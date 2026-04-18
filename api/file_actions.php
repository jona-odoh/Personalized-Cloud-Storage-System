<?php
// api/file_actions.php
require_once '../core/Auth.php';
require_once '../core/FileManager.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$fileManager = new FileManager($pdo);
$action = $_POST['action'] ?? '';

if ($action === 'trash') {
     $id = (int)($_POST['id'] ?? 0);
     if ($id <= 0) {
         echo json_encode(['status' => false, 'message' => 'Invalid file ID']);
         exit;
     }

     $success = $fileManager->moveToTrash($user['id'], $id);
     
     // Optionally adjust quota down here? No, trash files still take up space until permanently deleted.
     echo json_encode(['status' => $success, 'message' => $success ? 'Moved to trash' : 'Failed to move to trash']);
     exit;
}

if ($action === 'rename') {
     $id = (int)($_POST['id'] ?? 0);
     $name = trim($_POST['name'] ?? '');
     
     if (empty($name)) {
         echo json_encode(['status' => false, 'message' => 'Name cannot be empty']);
         exit;
     }
     
     $stmt = $pdo->prepare("UPDATE files SET original_name = ? WHERE id = ? AND user_id = ?");
     $success = $stmt->execute([$name, $id, $user['id']]);
     
     echo json_encode(['status' => $success, 'message' => $success ? 'Renamed successfully' : 'Failed to rename']);
     exit;
}

if ($action === 'star') {
     $id = (int)($_POST['id'] ?? 0);
     $stmt = $pdo->prepare("UPDATE files SET is_starred = NOT is_starred WHERE id = ? AND user_id = ?");
     $success = $stmt->execute([$id, $user['id']]);
     
     echo json_encode(['status' => $success, 'message' => $success ? 'Star toggled' : 'Failed to toggle star']);
     exit;
}

echo json_encode(['status' => false, 'message' => 'Invalid action']);
