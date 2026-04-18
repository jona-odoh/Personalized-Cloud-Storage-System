<?php
// core/FileManager.php

class FileManager {
    private $pdo;
    private $storagePath;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->storagePath = realpath(__DIR__ . '/../storage/users/');
    }

    private function getUserStorageLimit($userId) {
        $stmt = $this->pdo->prepare("SELECT quota_limit_bytes, quota_used_bytes FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    private function updateUserQuota($userId, $bytesDelta) {
        // $bytesDelta can be negative when deleting
        $stmt = $this->pdo->prepare("UPDATE users SET quota_used_bytes = quota_used_bytes + ? WHERE id = ?");
        $stmt->execute([$bytesDelta, $userId]);
    }

    public function uploadFile($userId, $folderId, $fileData) {
        // $fileData corresponds to $_FILES['file']
        
        // Check current quota
        $quota = $this->getUserStorageLimit($userId);
        if ($quota['quota_used_bytes'] + $fileData['size'] > $quota['quota_limit_bytes']) {
            return ['status' => false, 'message' => 'Upload failed. Storage quota exceeded.'];
        }

        // Store file physically
        $userDir = $this->storagePath . '/' . $userId;
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $userDir . '/' . $uniqueName;

        if (move_uploaded_file($fileData['tmp_name'], $destination)) {
            // Register in database
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO files (user_id, folder_id, original_name, stored_name, extension, mime_type, size) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $folderId,
                    $fileData['name'],
                    $uniqueName,
                    $extension,
                    $fileData['type'],
                    $fileData['size']
                ]);
                $fileId = $this->pdo->lastInsertId();

                // Update quota
                $this->updateUserQuota($userId, $fileData['size']);
                
                // Log activity
                $this->logActivity($userId, 'upload_file', $fileId, $folderId, "Uploaded: {$fileData['name']}");

                return ['status' => true, 'message' => 'Upload successful.', 'file' => [
                    'id' => $fileId,
                    'name' => $fileData['name']
                ]];
            } catch (PDOException $e) {
                // Remove physically if DB fails
                unlink($destination);
                return ['status' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }
        }
        
        return ['status' => false, 'message' => 'Failed to move uploaded file.'];
    }

    public function getFiles($userId, $folderId = null) {
        if ($folderId === null) {
            $stmt = $this->pdo->prepare("SELECT * FROM files WHERE user_id = ? AND folder_id IS NULL AND in_trash = FALSE ORDER BY created_at DESC");
            $stmt->execute([$userId]);
        } else {
             $stmt = $this->pdo->prepare("SELECT * FROM files WHERE user_id = ? AND folder_id = ? AND in_trash = FALSE ORDER BY created_at DESC");
             $stmt->execute([$userId, $folderId]);
        }
        return $stmt->fetchAll();
    }
    
    public function getFile($userId, $fileId) {
        $stmt = $this->pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
        $stmt->execute([$fileId, $userId]);
        return $stmt->fetch();
    }

    public function moveToTrash($userId, $fileId) {
        $stmt = $this->pdo->prepare("UPDATE files SET in_trash = TRUE, trashed_at = NOW() WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$fileId, $userId])) {
            $this->logActivity($userId, 'trash_file', $fileId, null, "Moved to trash");
            return true;
        }
        return false;
    }

    private function logActivity($userId, $action, $fileId, $folderId, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO activities (user_id, action, file_id, folder_id, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $fileId, $folderId, $description]);
    }
}
