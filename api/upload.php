<?php
// api/upload.php
require_once '../core/Auth.php';
require_once '../core/FileManager.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$fileManager = new FileManager($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    
    $folderId = isset($_POST['folder_id']) && is_numeric($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
    $fileData = $_FILES['file'];

    // Check for upload errors
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        $msg = $errorMessages[$fileData['error']] ?? 'Unknown upload error.';
        echo json_encode(['status' => false, 'message' => $msg]);
        exit;
    }

    $result = $fileManager->uploadFile($user['id'], $folderId, $fileData);
    echo json_encode($result);
    exit;
}

echo json_encode(['status' => false, 'message' => 'No file received.']);
