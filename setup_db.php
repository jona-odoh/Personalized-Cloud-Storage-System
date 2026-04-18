<?php
require_once __DIR__ . '/config/database.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'free', 'premium') DEFAULT 'free',
        avatar VARCHAR(255) DEFAULT 'default.png',
        quota_limit_bytes BIGINT DEFAULT 1073741824, -- 1GB default
        quota_used_bytes BIGINT DEFAULT 0,
        status ENUM('active', 'suspended') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS folders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        parent_id INT NULL,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        folder_id INT NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL UNIQUE,
        extension VARCHAR(10),
        mime_type VARCHAR(100),
        size BIGINT NOT NULL,
        is_starred BOOLEAN DEFAULT FALSE,
        in_trash BOOLEAN DEFAULT FALSE,
        trashed_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS shares (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shared_by INT NOT NULL,
        file_id INT NULL,
        folder_id INT NULL,
        shared_with_email VARCHAR(150) NULL,
        token VARCHAR(64) UNIQUE,
        password_hash VARCHAR(255) NULL,
        access_level ENUM('view', 'edit') DEFAULT 'view',
        download_limit INT NULL,
        downloads_count INT DEFAULT 0,
        expires_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (shared_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
        FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        file_id INT NULL,
        folder_id INT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE SET NULL,
        FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL
    )"
];

echo "<h2>Database Setup</h2><ul>";
foreach ($queries as $query) {
    try {
        $pdo->exec($query);
        echo "<li>Successfully executed query.</li>";
    } catch (PDOException $e) {
        echo "<li style='color:red;'>Error executing query: " . $e->getMessage() . "</li>";
    }
}
echo "</ul>";

// Default Admin user
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@admin.com'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@admin.com', ?, 'admin')");
        $stmt->execute([$adminPass]);
        echo "<p>Default admin created. <b>Email:</b> admin@admin.com <b>Password:</b> admin123</p>";
    } else {
        echo "<p>Default admin already exists.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error creating admin: " . $e->getMessage() . "</p>";
}
echo "<p>Done.</p>";
