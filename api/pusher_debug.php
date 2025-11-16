<?php
// api/pusher_debug.php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PUSHER DEBUG TEST ===\n\n";

// Step 1: Basic PHP test
echo "Step 1: Basic PHP... ";
echo "OK\n";

// Step 2: Check if pusher.php exists
echo "Step 2: Check pusher.php... ";
if (file_exists('../config/pusher.php')) {
    echo "EXISTS\n";
} else {
    echo "MISSING - creating simple version...\n";
    
    // Create a minimal pusher.php
    file_put_contents('../config/pusher.php', '<?php
define("PUSHER_APP_ID", "2065620");
define("PUSHER_KEY", "fe6f264f2fba2f7bc4a2"); 
define("PUSHER_SECRET", "7cf64dce7ff9a89e0450");
define("PUSHER_CLUSTER", "us2");
function getPusher() { 
    echo "Pusher function called\n";
    return null; 
}
?>');
    echo "Created minimal pusher.php\n";
}

// Step 3: Try to include pusher.php
echo "Step 3: Include pusher.php... ";
try {
    require_once '../config/pusher.php';
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

// Step 4: Check Pusher constants
echo "Step 4: Check constants... ";
if (defined('PUSHER_APP_ID')) {
    echo "PUSHER_APP_ID=" . PUSHER_APP_ID . "\n";
} else {
    echo "Constants not defined\n";
}

// Step 5: Test getPusher function
echo "Step 5: Test getPusher... ";
if (function_exists('getPusher')) {
    $pusher = getPusher();
    echo "FUNCTION EXISTS\n";
} else {
    echo "FUNCTION NOT FOUND\n";
}

// Step 6: Check for Composer
echo "Step 6: Check Composer... ";
if (file_exists('../vendor/autoload.php')) {
    echo "AUTOLOAD EXISTS\n";
    
    // Try to load Pusher SDK
    echo "Step 7: Load Pusher SDK... ";
    try {
        require_once '../vendor/autoload.php';
        
        if (class_exists('Pusher\Pusher')) {
            echo "PUSHER SDK LOADED\n";
            
            // Test actual Pusher connection
            echo "Step 8: Test Pusher connection... ";
            try {
                $pusher = new Pusher\Pusher(
                    PUSHER_KEY,
                    PUSHER_SECRET, 
                    PUSHER_APP_ID,
                    ['cluster' => PUSHER_CLUSTER, 'useTLS' => true]
                );
                
                $result = $pusher->trigger('test-channel', 'test-event', ['test' => true]);
                echo "SUCCESS - Event sent\n";
                
            } catch (Exception $e) {
                echo "FAILED: " . $e->getMessage() . "\n";
            }
            
        } else {
            echo "PUSHER SDK CLASS NOT FOUND\n";
        }
        
    } catch (Exception $e) {
        echo "AUTOLOAD FAILED: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "NO COMPOSER AUTOLOAD\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>