<?php
session_start();
echo "<h1>Path Test</h1>";

// Check different possible paths
$paths = [
    '../Lib/phpqrcode/qrlib.php',
    '../../Lib/phpqrcode/qrlib.php', 
    '/home/u834808878/domains/tech-ideapad.com/public_html/Lib/phpqrcode/qrlib.php',
    'Lib/phpqrcode/qrlib.php',
    '../public_html/Lib/phpqrcode/qrlib.php'
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        echo "✅ FOUND: $path<br>";
    } else {
        echo "❌ NOT FOUND: $path<br>";
    }
}

echo "<h3>Current directory:</h3>";
echo getcwd();

echo "<h3>Directory listing of ../</h3>";
$parent = dirname(getcwd());
if (is_dir($parent)) {
    $files = scandir($parent);
    foreach ($files as $file) {
        echo $file . "<br>";
    }
}
?>