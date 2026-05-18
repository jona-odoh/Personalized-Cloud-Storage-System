<?php
// download_assets.php

ini_set('max_execution_time', 300);

echo "<h2>Downloading Offline Assets...</h2>";

$tailwindUrl = 'https://cdn.tailwindcss.com';
$faUrl = 'https://use.fontawesome.com/releases/v6.4.0/fontawesome-free-6.4.0-web.zip';

// Ensure directories exist
@mkdir(__DIR__ . '/assets/js', 0777, true);

echo "1. Downloading Tailwind CSS...<br>";
$tailwindContent = file_get_contents($tailwindUrl);
if ($tailwindContent) {
    file_put_contents(__DIR__ . '/assets/js/tailwindcss.js', $tailwindContent);
    echo "<span style='color:green;'>&#10004; Tailwind CSS downloaded successfully.</span><br><br>";
} else {
    echo "<span style='color:red;'>&#10008; Failed to download Tailwind CSS.</span><br><br>";
}

echo "2. Downloading FontAwesome (This may take a moment)...<br>";
$zipFile = __DIR__ . '/fa.zip';
$faContent = file_get_contents($faUrl);
if ($faContent) {
    file_put_contents($zipFile, $faContent);
    
    echo "3. Extracting FontAwesome using PowerShell...<br>";
    @mkdir(__DIR__ . '/fa_temp', 0777, true);
    
    // Execute PowerShell command to extract the zip file
    $psCommand = 'powershell -NoProfile -ExecutionPolicy Bypass -Command "Expand-Archive -Path \'' . $zipFile . '\' -DestinationPath \'' . __DIR__ . '/fa_temp\' -Force"';
    shell_exec($psCommand);
    
    if (is_dir(__DIR__ . '/fa_temp/fontawesome-free-6.4.0-web')) {
        // Move css and webfonts to the correct directory
        $baseFaDir = __DIR__ . '/fa_temp/fontawesome-free-6.4.0-web';
        
        // Remove existing fontawesome directory if it exists to avoid rename errors
        if (is_dir(__DIR__ . '/assets/fontawesome')) {
             // Basic recursive delete
             $it = new RecursiveDirectoryIterator(__DIR__ . '/assets/fontawesome', RecursiveDirectoryIterator::SKIP_DOTS);
             $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
             foreach($files as $file) {
                 if ($file->isDir()) @rmdir($file->getRealPath());
                 else @unlink($file->getRealPath());
             }
             @rmdir(__DIR__ . '/assets/fontawesome');
        }
        
        @mkdir(__DIR__ . '/assets/fontawesome', 0777, true);
        rename($baseFaDir . '/css', __DIR__ . '/assets/fontawesome/css');
        rename($baseFaDir . '/webfonts', __DIR__ . '/assets/fontawesome/webfonts');
        
        // Clean up zip and temp files
        @unlink($zipFile);
        $it = new RecursiveDirectoryIterator(__DIR__ . '/fa_temp', RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir(__DIR__ . '/fa_temp');
        
        echo "<span style='color:green;'>&#10004; FontAwesome downloaded and extracted successfully.</span><br><br>";
    } else {
        echo "<span style='color:red;'>&#10008; Failed to extract FontAwesome zip. Make sure PowerShell is accessible.</span><br><br>";
    }
} else {
    echo "<span style='color:red;'>&#10008; Failed to download FontAwesome.</span><br><br>";
}

echo "<h3>All done! You can now use the app offline. Please delete this script (download_assets.php) for security.</h3>";
echo "<a href='index.php'>Go to App</a>";
?>
