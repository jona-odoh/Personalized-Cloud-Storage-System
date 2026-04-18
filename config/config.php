<?php
// config/config.php
session_start();

define('APP_NAME', 'Personal Cloud Storage');
define('BASE_URL', 'http://localhost/Personalized%20Cloud%20Storage%20System');
define('STORAGE_PATH', __DIR__ . '/../storage/users/');
define('EMAIL_LOG_FILE', __DIR__ . '/../logs/emails.log');

define('FREE_QUOTA_BYTES', 1073741824); // 1 GB
define('PREMIUM_QUOTA_BYTES', 53687091200); // 50 GB

define('MAX_UPLOAD_FREE', 104857600); // 100 MB
define('MAX_UPLOAD_PREMIUM', 1073741824); // 1 GB

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Ensure necessary directories exist
$dirs = [
    __DIR__ . '/../storage/users/',
    __DIR__ . '/../storage/trash/',
    __DIR__ . '/../logs/'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
