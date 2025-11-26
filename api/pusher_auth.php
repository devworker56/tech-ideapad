<?php
// pusher_auth.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable detailed logging
error_log("=== PUSHER AUTH ENDPOINT HIT ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("GET Parameters: " . json_encode($_GET));

// Check if required files exist
$config_file = '../config/database.php';
$pusher_file = '../config/pusher.php';

error_log("Config file exists: " . (file_exists($config_file) ? 'YES' : 'NO'));
error_log("Pusher file exists: " . (file_exists($pusher_file) ? 'YES' : 'NO'));

require_once $config_file;
require_once $pusher_file;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $channel = $_GET['channel'] ?? '';
    $socket_id = $_GET['socket_id'] ?? '';

    error_log("Auth request - Channel: $channel, Socket ID: $socket_id");

    if (empty($channel) || empty($socket_id)) {
        error_log("❌ Missing channel or socket_id");
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing channel or socket_id',
            'received' => ['channel' => $channel, 'socket_id' => $socket_id]
        ]);
        exit;
    }

    // Verify this is a valid module channel
    if (strpos($channel, 'private-module_') === 0) {
        $moduleId = str_replace('private-module_', '', $channel);
        
        error_log("🔍 Validating module: " . $moduleId);
        
        // Verify module exists in database and is active
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, module_id, name, status FROM modules WHERE module_id = ? AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute([$moduleId]);
        $module = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($module) {
            error_log("✅ Module found and active: " . $module['name']);
            
            // Use your existing Pusher configuration to generate auth
            $pusher = getPusher();
            
            if ($pusher) {
                // Generate auth signature using Pusher library
                $auth = $pusher->socket_auth($channel, $socket_id);
                
                error_log("✅ Pusher auth generated successfully");
                
                echo json_encode([
                    'success' => true,
                    'auth' => $auth,
                    'module' => [
                        'id' => $module['id'],
                        'module_id' => $module['module_id'],
                        'name' => $module['name']
                    ]
                ]);
            } else {
                error_log("❌ Pusher initialization failed");
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Pusher service unavailable'
                ]);
            }
            
        } else {
            error_log("❌ Module not found or inactive: " . $moduleId);
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Module not found or inactive: ' . $moduleId
            ]);
        }
        
    } else {
        error_log("❌ Invalid channel format: " . $channel);
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid channel format. Must start with private-module_'
        ]);
    }

} catch (Exception $e) {
    error_log("❌ Pusher auth exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>