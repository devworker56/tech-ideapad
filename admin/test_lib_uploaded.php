<?php
session_start();
echo "<h1>Testing Lib Folder Upload</h1>";

$lib_path = '../Lib/phpqrcode/qrlib.php';

if (file_exists($lib_path)) {
    echo "✅ SUCCESS! Lib folder found on server!<br>";
    echo "Path: $lib_path<br>";
    
    // Test including the library
    require_once $lib_path;
    echo "✅ QR Library loaded successfully!<br>";
    
    // Test generating a QR code
    $test_file = '../qr_codes/upload_test.png';
    QRcode::png('UPLOAD TEST SUCCESS', $test_file);
    
    if (file_exists($test_file)) {
        echo "✅ QR code generated successfully!<br>";
        echo "<img src='$test_file' alt='Test QR Code'><br>";
        echo "File created: $test_file<br>";
    } else {
        echo "❌ QR code file was not created<br>";
    }
} else {
    echo "❌ Lib folder still not found at: $lib_path<br>";
    echo "Please check:<br>";
    echo "1. The Lib folder is in public_html/<br>";
    echo "2. The phpqrcode folder is inside Lib/<br>";
    echo "3. qrlib.php exists in Lib/phpqrcode/<br>";
    
    echo "<h3>Checking directory structure:</h3>";
    if (is_dir('../Lib')) {
        echo "✅ Lib folder exists<br>";
        $items = scandir('../Lib');
        foreach ($items as $item) {
            if ($item != '.' && $item != '..') {
                echo "&nbsp;&nbsp;📁 $item<br>";
                if ($item == 'phpqrcode') {
                    $qr_files = scandir('../Lib/phpqrcode');
                    foreach ($qr_files as $qr_file) {
                        if ($qr_file != '.' && $qr_file != '..') {
                            echo "&nbsp;&nbsp;&nbsp;&nbsp;📄 $qr_file<br>";
                        }
                    }
                }
            }
        }
    } else {
        echo "❌ Lib folder does not exist<br>";
    }
}
?>