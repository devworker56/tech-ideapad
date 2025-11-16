<?php
session_start();
echo "<h1>QR Code Test</h1>";

// Test 1: Check if library file exists
$library_path = '../Lib/phpqrcode/qrlib.php';
if (file_exists($library_path)) {
    echo "✅ Library found: " . $library_path . "<br>";
    
    // Test 2: Try to include the library
    try {
        require_once $library_path;
        echo "✅ Library included successfully<br>";
        
        // Test 3: Try to generate a QR code
        $test_file = '../qr_codes/test_' . time() . '.png';
        QRcode::png('TEST123', $test_file, QR_ECLEVEL_L, 10, 2);
        
        if (file_exists($test_file)) {
            echo "✅ QR code generated successfully!<br>";
            echo "<img src='$test_file' alt='Test QR'><br>";
            echo "File: " . $test_file;
        } else {
            echo "❌ QR code file not created<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error including library: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Library NOT found: " . $library_path . "<br>";
    echo "Please make sure the phpqrcode folder is in your Lib directory<br>";
}

// Test 4: Check directory permissions
$qr_dir = '../qr_codes/';
if (!file_exists($qr_dir)) {
    echo "⚠️ QR codes directory doesn't exist, creating...<br>";
    mkdir($qr_dir, 0755, true);
}
if (file_exists($qr_dir)) {
    echo "✅ QR codes directory exists: " . $qr_dir . "<br>";
    echo "Directory writable: " . (is_writable($qr_dir) ? '✅ Yes' : '❌ No') . "<br>";
}
?>