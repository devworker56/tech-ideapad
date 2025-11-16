<?php
// api/pusher_test.php - QUICK TEST FILE
header('Content-Type: application/json');
require_once '../config/pusher.php';

// Test Pusher immediately
$pusher = getPusher();

if (!$pusher) {
    echo json_encode(['success' => false, 'message' => 'Pusher initialization failed']);
    exit;
}

try {
    // Test sending an event
    $result = $pusher->trigger('test-channel', 'test-event', [
        'message' => 'Hello from Pusher Test',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Pusher test successful!',
        'result' => $result,
        'credentials' => [
            'app_id' => PUSHER_APP_ID,
            'key' => PUSHER_KEY,
            'cluster' => PUSHER_CLUSTER
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Pusher test failed',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>