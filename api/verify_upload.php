<?php
// api/verify_upload.php
header('Content-Type: text/plain');

echo "=== VERIFYING PUSHER UPLOAD ===\n\n";

$checks = [
    'vendor/autoload.php' => 'Composer Autoload',
    'vendor/pusher/pusher-php-server/src/Pusher.php' => 'Pusher Main File',
    'vendor/pusher/pusher-php-server/src/PusherInstance.php' => 'Pusher Instance',
    'vendor/pusher/pusher-php-server/src/PusherInterface.php' => 'Pusher Interface',
];

foreach ($checks as $file => $description) {
    echo "$description: ";
    if (file_exists('../' . $file)) {
        echo "✅ EXISTS\n";
    } else {
        echo "❌ MISSING\n";
    }
}

echo "\n=== TESTING PUSHER LOADING ===\n";

require_once '../vendor/autoload.php';

echo "Pusher\\Pusher class: ";
if (class_exists('Pusher\\Pusher')) {
    echo "✅ LOADED\n";
    
    // Test instantiation
    try {
        $pusher = new Pusher\Pusher(
            'fe6f264f2fba2f7bc4a2',
            '7cf64dce7ff9a89e0450',
            '2065620',
            ['cluster' => 'us2', 'useTLS' => true]
        );
        echo "Instantiation: ✅ SUCCESS\n";
        echo "🎯 PUSHER IS NOW WORKING!\n";
    } catch (Exception $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ NOT LOADED\n";
}
?>