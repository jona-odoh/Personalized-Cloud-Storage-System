<?php
// core/FolderManager.php

class FolderManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createFolder($userId, $name, $parentId = null) {
        // Prevent duplicate folder names in the same directory
        $stmt = $this->pdo->prepare("SELECT id FROM folders WHERE user_id = ? AND name = ? AND (parent_id = ? OR (parent_id IS NULL AND ? IS NULL))");
        $stmt->execute([$userId, $name, $parentId, $parentId]);
        if ($stmt->fetch()) {
            return ['status' => false, 'message' => 'A folder with this name already exists here.'];
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO folders (user_id, parent_id, name) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $parentId, $name]);
            $folderId = $this->pdo->lastInsertId();
            
            $this->logActivity($userId, 'create_folder', null, $folderId, "Created folder: $name");
            
            return ['status' => true, 'message' => 'Folder created.', 'folder_id' => $folderId];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getFolders($userId, $parentId = null) {
        if ($parentId === null) {
            $stmt = $this->pdo->prepare("SELECT * FROM folders WHERE user_id = ? AND parent_id IS NULL ORDER BY name ASC");
            $stmt->execute([$userId]);
        } else {
             $stmt = $this->pdo->prepare("SELECT * FROM folders WHERE user_id = ? AND parent_id = ? ORDER BY name ASC");
             $stmt->execute([$userId, $parentId]);
        }
        return $stmt->fetchAll();
    }

    public function getFolder($userId, $folderId) {
        $stmt = $this->pdo->prepare("SELECT * FROM folders WHERE id = ? AND user_id = ?");
        $stmt->execute([$folderId, $userId]);
        return $stmt->fetch();
    }

    public function getBreadcrumbs($userId, $folderId) {
        $breadcrumbs = [];
        $current = $this->getFolder($userId, $folderId);
        
        while ($current) {
            array_unshift($breadcrumbs, $current); // Add to beginning
            if ($current['parent_id']) {
                $current = $this->getFolder($userId, $current['parent_id']);
            } else {
                $current = null;
            }
        }
        return $breadcrumbs;
    }
    
    public function renameFolder($userId, $folderId, $newName) {
        $stmt = $this->pdo->prepare("UPDATE folders SET name = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$newName, $folderId, $userId]);
        $this->logActivity($userId, 'rename_folder', null, $folderId, "Renamed folder to: $newName");
        return true;
    }

    public function deleteFolder($userId, $folderId) {
        // Find all subfolders recursively using CTE
        $stmt = $this->pdo->prepare("
            WITH RECURSIVE FolderHierarchy AS (
                SELECT id FROM folders WHERE id = ? AND user_id = ?
                UNION ALL
                SELECT f.id FROM folders f
                INNER JOIN FolderHierarchy fh ON f.parent_id = fh.id
            )
            SELECT id FROM FolderHierarchy
        ");
        $stmt->execute([$folderId, $userId]);
        $folderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($folderIds)) {
            return false; // Folder not found or not owned by user
        }

        // Get all files in these folders
        $placeholders = implode(',', array_fill(0, count($folderIds), '?'));
        $params = $folderIds;
        $params[] = $userId;
        
        $stmt = $this->pdo->prepare("SELECT size, stored_name FROM files WHERE folder_id IN ($placeholders) AND user_id = ?");
        $stmt->execute($params);
        $files = $stmt->fetchAll();

        $totalSizeToReclaim = 0;
        $storagePath = realpath(__DIR__ . '/../storage/users/') . '/' . $userId;

        foreach ($files as $file) {
            $filePath = $storagePath . '/' . $file['stored_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $totalSizeToReclaim += $file['size'];
        }

        if ($totalSizeToReclaim > 0) {
            $stmt = $this->pdo->prepare("UPDATE users SET quota_used_bytes = quota_used_bytes - ? WHERE id = ?");
            $stmt->execute([$totalSizeToReclaim, $userId]);
        }

        // Delete the root folder. DB cascade handles subfolders and files.
        $stmt = $this->pdo->prepare("DELETE FROM folders WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$folderId, $userId])) {
            $this->logActivity($userId, 'delete_folder', null, null, "Permanently deleted folder ID: $folderId and its contents");
            return true;
        }

        return false;
    }

    private function logActivity($userId, $action, $fileId, $folderId, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO activities (user_id, action, file_id, folder_id, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $fileId, $folderId, $description]);
    }
}
