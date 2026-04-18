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

    private function logActivity($userId, $action, $fileId, $folderId, $description) {
        $stmt = $this->pdo->prepare("INSERT INTO activities (user_id, action, file_id, folder_id, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $fileId, $folderId, $description]);
    }
}
