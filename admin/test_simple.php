<?php
session_start();
echo "<h1>Simple QR Test</h1>";

// Test basic file inclusion
$library_path = '../Lib/phpqrcode/qrlib.php';
if (file_exists($library_path)) {
    echo "✅ Library exists<br>";
    
    // Try to include just the basic files
    include '../Lib/phpqrcode/qrconst.php';
    include '../Lib/phpqrcode/qrconfig.php';
    echo "✅ Basic files included<br>";
    
    // Test creating a simple image
    $test_file = '../qr_codes/simple_test.png';
    $im = imagecreate(100, 100);
    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 10, 10, "TEST", $black);
    imagepng($im, $test_file);
    imagedestroy($im);
    
    if (file_exists($test_file)) {
        echo "✅ Basic image creation works<br>";
        echo "<img src='$test_file'><br>";
    }
    
} else {
    echo "❌ Library not found at: $library_path<br>";
}

// Show current directory structure
echo "<h3>Current directory:</h3>";
echo getcwd();
?>