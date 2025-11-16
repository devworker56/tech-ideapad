<?php
// api/test_realtime.php
header('Content-Type: application/json');

function notify_pusher($event, $data, $channel) {
    // Your Solution 1 code here...
}

// Test real-time event
$result = notify_pusher('test_realtime', [
    'message' => 'This should appear instantly in the app!',
    'timestamp' => date('Y-m-d H:i:s'),
    'random_id' => uniqid()
], 'user_TEST123');

echo json_encode([
    'success' => true,
    'message' => 'Real-time test event sent',
    'pusher_result' => $result
]);
?>