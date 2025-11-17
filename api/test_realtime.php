<?php
// api/test_realtime.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Notify via Pusher using PHP SDK
 */
function notify_pusher($event, $data, $channel) {
    try {
        // Include your Pusher config
        require_once '../config/pusher.php';
        
        $pusher = getPusher();
        if (!$pusher) {
            error_log("❌ Pusher not initialized");
            return false;
        }
        
        $result = $pusher->trigger($channel, $event, $data);
        error_log("✅ Pusher SDK: $event to $channel - SUCCESS");
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Pusher SDK error: " . $e->getMessage());
        return false;
    }
}

// Test real-time event
$result = notify_pusher('test_realtime', [
    'message' => 'This should appear instantly in the app!',
    'timestamp' => date('Y-m-d H:i:s'),
    'random_id' => uniqid(),
    'status' => 'success',
    'test_data' => [
        'user' => 'TEST123',
        'action' => 'real_time_test'
    ]
], 'user_TEST123');

echo json_encode([
    'success' => true,
    'message' => 'Real-time test event sent',
    'pusher_result' => $result,
    'channel' => 'user_TEST123',
    'event' => 'test_realtime',
    'data_sent' => [
        'message' => 'This should appear instantly in the app!',
        'timestamp' => date('Y-m-d H:i:s'),
        'random_id' => uniqid()
    ]
]);
?>