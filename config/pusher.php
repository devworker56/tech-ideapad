<?php
// config/pusher.php
require_once __DIR__ . '/../vendor/autoload.php';

// Pusher Configuration - FIXED VERSION
define('PUSHER_APP_ID', '2065620');
define('PUSHER_KEY', 'fe6f264f2fba2f7bc4a2'); 
define('PUSHER_SECRET', '7cf64dce7ff9a89e0450');
define('PUSHER_CLUSTER', 'us2');

function getPusher() {
    try {
        $pusher = new Pusher\Pusher(
            PUSHER_KEY,
            PUSHER_SECRET, 
            PUSHER_APP_ID,
            [
                'cluster' => PUSHER_CLUSTER,
                'useTLS' => true,
                'encrypted' => true,
                'debug' => true, // ADD THIS
                'curl_options' => [ // ADD THIS
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_TIMEOUT => 10
                ]
            ]
        );
        
        error_log("✅ Pusher initialized: App " . PUSHER_APP_ID . " on cluster " . PUSHER_CLUSTER);
        return $pusher;
        
    } catch (Exception $e) {
        error_log("❌ Pusher init failed: " . $e->getMessage());
        return null;
    }
}
?>