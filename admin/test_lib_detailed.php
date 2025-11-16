<?php
session_start();
echo "<h1>Detailed Lib Search</h1>";

// Get absolute path to parent directory
$parent_dir = dirname(getcwd());
echo "Parent directory: " . $parent_dir . "<br><br>";

// Check if Lib exists with different cases
$possible_paths = [
    $parent_dir . '/Lib',
    $parent_dir . '/lib', 
    $parent_dir . '/LIB',
    $parent_dir . '/LiB',
];

foreach ($possible_paths as $path) {
    if (is_dir($path)) {
        echo "✅ DIRECTORY EXISTS: $path<br>";
        
        // Check phpqrcode subfolder
        $qr_path = $path . '/phpqrcode';
        if (is_dir($qr_path)) {
            echo "&nbsp;&nbsp;✅ phpqrcode folder exists<br>";
            
            // List contents
            $files = scandir($qr_path);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;📄 $file<br>";
                }
            }
        } else {
            echo "&nbsp;&nbsp;❌ phpqrcode folder NOT found<br>";
        }
    } else {
        echo "❌ Directory NOT found: $path<br>";
    }
}

echo "<hr><h3>Full directory listing with file permissions:</h3>";
$items = scandir($parent_dir);
foreach ($items as $item) {
    if ($item != '.' && $item != '..') {
        $full_path = $parent_dir . '/' . $item;
        $perms = fileperms($full_path);
        $type = is_dir($full_path) ? '📁 Directory' : '📄 File';
        echo "$type: $item (perms: " . decoct($perms & 0777) . ")<br>";
    }
}
?>