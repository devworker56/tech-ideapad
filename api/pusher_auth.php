<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Simple authentication for ESP32 modules
$channel = $_GET['channel'] ?? '';
$socketId = $_GET['socket_id'] ?? '';

if (empty($channel) || empty($socketId)) {
    echo json_encode(['success' => false, 'message' => 'Missing channel or socket_id']);
    exit;
}

// Verify this is a valid module channel
if (strpos($channel, 'private-module_') === 0) {
    $moduleId = str_replace('private-module_', '', $channel);
    
    // Verify module exists in database
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id FROM modules WHERE module_id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$moduleId]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($module) {
        // Generate auth signature (for Pusher private channels)
        $stringToSign = $socketId . ':' . $channel;
        $auth = hash_hmac('sha256', $stringToSign, '7cf64dce7ff9a89e0450', false);
        
        echo json_encode([
            'success' => true,
            'auth' => PUSHER_KEY . ':' . $auth
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid module']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid channel']);
}
?>