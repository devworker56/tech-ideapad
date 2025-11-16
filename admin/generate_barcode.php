<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Include barcode generator library
require_once '../Lib/phpqrcode/qrlib.php';

$database = new Database();
$db = $database->getConnection();

if ($_POST) {
    $data = $_POST['data'] ?? '';
    $type = $_POST['type'] ?? 'CODE128';
    $width = $_POST['width'] ?? 2;
    $height = $_POST['height'] ?? 1;
    
    header('Content-Type: application/json');
    
    if (empty($data)) {
        echo json_encode(['success' => false, 'message' => 'Barcode data is required']);
        exit;
    }
    
    try {
        // Generate barcode
        $barcodeFile = generateBarcode($data, $type, $width, $height);
        
        echo json_encode([
            'success' => true,
            'barcode_url' => $barcodeFile,
            'html' => '<img src="' . $barcodeFile . '" alt="Barcode" class="img-fluid" style="max-width: 100%; height: auto;">'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error generating barcode: ' . $e->getMessage()]);
    }
    exit;
}

function generateBarcode($data, $type = 'CODE128', $width = 2, $height = 1) {
    $barcodeDir = "../barcodes/";
    if (!file_exists($barcodeDir)) {
        mkdir($barcodeDir, 0755, true);
    }
    
    $filename = 'barcode_' . md5($data . $type . time()) . '.png';
    $filepath = $barcodeDir . $filename;
    
    // Use the barcode generator
    $barcode = new \Com\Tecnick\Barcode\Barcode();
    
    try {
        $barcodeObj = $barcode->getBarcodeObj(
            $type,
            $data,
            $width * 150,  // width in pixels
            $height * 50,  // height in pixels
            'black',
            [-2, -2, -2, -2]
        );
        
        $imageData = $barcodeObj->getPngData();
        file_put_contents($filepath, $imageData);
        
        return $barcodeDir . $filename;
    } catch (Exception $e) {
        throw new Exception('Barcode generation failed: ' . $e->getMessage());
    }
}
?>