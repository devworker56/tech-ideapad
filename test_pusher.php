<?php
// test_pusher.php
echo "🧪 Testing Pusher Connection...\n\n";

require_once 'config/pusher.php';

$pusher = getPusher();
if (!$pusher) {
    echo "❌ Could not initialize Pusher\n";
    exit(1);
}

try {
    // Test data
    $channel = 'test-channel';
    $event = 'test-event';
    $data = [
        'message' => 'Hello Pusher!',
        'timestamp' => date('Y-m-d H:i:s'),
        'test' => true
    ];
    
    echo "📡 Sending test event...\n";
    echo "   Channel: $channel\n";
    echo "   Event: $event\n";
    echo "   Data: " . json_encode($data) . "\n\n";
    
    $result = $pusher->trigger($channel, $event, $data);
    
    echo "✅ Pusher test SUCCESSFUL!\n";
    echo "   Event sent successfully\n";
    echo "   Check your Pusher dashboard at: https://dashboard.pusher.com/apps/2065620\n";
    echo "   Look for events in the 'test-channel' channel\n\n";
    
} catch (Exception $e) {
    echo "❌ Pusher test FAILED:\n";
    echo "   Error: " . $e->getMessage() . "\n";
    
    // More detailed error information
    if (strpos($e->getMessage(), 'cURL error') !== false) {
        echo "   This might be a network/SSL issue\n";
    } elseif (strpos($e->getMessage(), 'authentication') !== false) {
        echo "   This might be an authentication issue (check your credentials)\n";
    }
}
?>