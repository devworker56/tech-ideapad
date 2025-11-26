<?php
// pusher_auth.php - SIMPLIFIED WORKING VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable detailed logging
error_log("=== PUSHER AUTH ENDPOINT HIT ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("GET Parameters: " . json_encode($_GET));

// Define Pusher credentials directly (from your config)
define('PUSHER_APP_ID', '2065620');
define('PUSHER_KEY', 'fe6f264f2fba2f7bc4a2'); 
define('PUSHER_SECRET', '7cf64dce7ff9a89e0450');
define('PUSHER_CLUSTER', 'us2');

// Check if required files exist
$config_file = '../config/database.php';

error_log("Database config file exists: " . (file_exists($config_file) ? 'YES' : 'NO'));

if (!file_exists($config_file)) {
    error_log("❌ Database config file missing");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database configuration missing']);
    exit;
}

require_once $config_file;

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
            'message' => 'Missing channel or socket_id'
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
            
            // MANUAL AUTHENTICATION (guaranteed to work)
            $string_to_sign = $socket_id . ':' . $channel;
            $auth_signature = hash_hmac('sha256', $string_to_sign, PUSHER_SECRET, false);
            $auth = PUSHER_KEY . ':' . $auth_signature;
            
            error_log("✅ Manual auth generated successfully");
            error_log("✅ String to sign: " . $string_to_sign);
            error_log("✅ Auth signature: " . $auth_signature);
            error_log("✅ Final auth: " . $auth);
            
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
    error_log("❌ Exception trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>