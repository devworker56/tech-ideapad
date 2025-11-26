<?php
// Enable maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Log every request
error_log("=== SESSIONS.PHP HIT ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Query String: " . ($_SERVER['QUERY_STRING'] ?? 'None'));
error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'None'));
error_log("Input Data: " . file_get_contents('php://input'));

// Check if required files exist
$config_file = '../config/database.php';
$functions_file = '../includes/functions.php';

error_log("Config file exists: " . (file_exists($config_file) ? 'YES' : 'NO'));
error_log("Functions file exists: " . (file_exists($functions_file) ? 'YES' : 'NO'));

// Continue with your existing code...
require_once $config_file;
require_once $functions_file;

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

// Log the request for debugging
error_log("Sessions API Request: action=$action, input=" . json_encode($input));

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    switch($action) {
        // ADD THE TEST CASE RIGHT HERE - at the beginning
        case 'test_simple':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                error_log("TEST_SIMPLE endpoint reached");
                echo json_encode([
                    'success' => true,
                    'message' => 'Simple POST test works!',
                    'data_received' => json_decode(file_get_contents('php://input'), true)
                ]);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST required for test']);
            }
            break;
            
        // YOUR EXISTING CASES FOLLOW...
        case 'start_session':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                startDonationSession($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'verify_session':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                verifySession($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'end_session':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                endDonationSession($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'get_active_session':
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                getActiveSession($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Sessions API Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function startDonationSession($db, $data) {
    error_log("startDonationSession called with: " . json_encode($data));
    
    $donor_id = $data['donor_id'] ?? '';
    $charity_id = $data['charity_id'] ?? '';
    $module_id = $data['module_id'] ?? '';
    
    if (empty($donor_id) || empty($charity_id) || empty($module_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: donor_id, charity_id, and module_id are required']);
        return;
    }
    
    // Verify donor exists
    $query = "SELECT id, user_id FROM donors WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$donor_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor not found']);
        return;
    }
    
    // Verify charity exists and is approved
    $query = "SELECT id, name FROM charities WHERE id = ? AND approved = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$charity_id]);
    $charity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$charity) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Charity not found or not approved']);
        return;
    }
    
    // Verify module exists and is active
    $query = "SELECT id, name FROM modules WHERE module_id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$module_id]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$module) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Module not found or not active']);
        return;
    }
    
    error_log("Donor, charity, and module verified successfully");
    
    try {
        $db->beginTransaction();
        
        // Generate unique session token
        $session_token = 'sess_' . bin2hex(random_bytes(16));
        
        // Create donation session record
        $query = "INSERT INTO donation_sessions (donor_id, charity_id, module_id, session_token, status) 
                  VALUES (?, ?, ?, ?, 'active')";
        $stmt = $db->prepare($query);
        $stmt->execute([$donor_id, $charity_id, $module_id, $session_token]);
        
        $session_id = $db->lastInsertId();
        
        // Create verifiable session record
        $transaction_hash = create_verifiable_donation_session(
            $donor_id,
            $donor['user_id'],
            $charity_id,
            $module_id,
            $session_id,
            $db
        );
        
        // Log activity
        log_activity($db, 'donor', $donor_id, 'donation_session_started', 
            "Donor {$donor['user_id']} started donation session for charity '{$charity['name']}' via module $module_id");
        
        $db->commit();
        
        // Notify via Pusher about session start
        $pusher_result = notify_pusher('session_started', [
            'session_id' => $session_id,
            'donor_id' => $donor_id,
            'charity_id' => $charity_id,
            'module_id' => $module_id,
            'charity_name' => $charity['name'],
            'timestamp' => date('Y-m-d H:i:s')
        ], "module_$module_id");

        error_log("Pusher notification result: " . ($pusher_result ? "SUCCESS" : "FAILED"));
        
        $response = [
            'success' => true, 
            'message' => 'Donation session started successfully',
            'session' => [
                'session_id' => $session_id,
                'session_token' => $session_token,
                'donor_id' => $donor_id,
                'donor_user_id' => $donor['user_id'],
                'charity_id' => $charity_id,
                'charity_name' => $charity['name'],
                'module_id' => $module_id,
                'module_name' => $module['name'],
                'transaction_hash' => $transaction_hash,
                'started_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', time() + 900), // 15 minutes
                'verifiable' => true
            ]
        ];
        
        error_log("Session created successfully: " . json_encode($response));
        echo json_encode($response);
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        error_log("Donation session error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to start donation session',
            'error' => $e->getMessage()
        ]);
    }
}

function verifySession($db, $data) {
    $session_id = $data['session_id'] ?? '';
    $session_token = $data['session_token'] ?? '';
    
    if (empty($session_id) && empty($session_token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Session ID or token required']);
        return;
    }
    
    $query = "SELECT ds.*, d.user_id as donor_user_id, c.name as charity_name, m.name as module_name
              FROM donation_sessions ds
              JOIN donors d ON ds.donor_id = d.id
              JOIN charities c ON ds.charity_id = c.id
              JOIN modules m ON ds.module_id = m.module_id
              WHERE (ds.id = ? OR ds.session_token = ?) AND ds.status = 'active' 
              AND ds.expires_at > NOW()";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$session_id, $session_token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session) {
        echo json_encode([
            'success' => true,
            'active' => true,
            'session' => $session
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'active' => false,
            'message' => 'Session not found or expired'
        ]);
    }
}

function endDonationSession($db, $data) {
    $session_id = $data['session_id'] ?? '';
    $session_token = $data['session_token'] ?? '';
    
    if (empty($session_id) && empty($session_token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Session ID or token required']);
        return;
    }
    
    $query = "UPDATE donation_sessions 
              SET status = 'completed', ended_at = NOW() 
              WHERE (id = ? OR session_token = ?) AND status = 'active'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$session_id, $session_token]);
    
    if ($stmt->rowCount() > 0) {
        // Get session details for logging
        $query = "SELECT ds.*, d.user_id, c.name as charity_name 
                  FROM donation_sessions ds
                  JOIN donors d ON ds.donor_id = d.id
                  JOIN charities c ON ds.charity_id = c.id
                  WHERE ds.id = ? OR ds.session_token = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$session_id, $session_token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log activity
        log_activity($db, 'donor', $session['donor_id'], 'donation_session_ended', 
            "Donor {$session['user_id']} ended donation session for charity '{$session['charity_name']}'");
        
        echo json_encode([
            'success' => true,
            'message' => 'Session ended successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Session not found or already ended'
        ]);
    }
}

function getActiveSession($db, $data) {
    $donor_id = $data['donor_id'] ?? '';
    $module_id = $data['module_id'] ?? '';
    
    if (empty($donor_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Donor ID required']);
        return;
    }
    
    $query = "SELECT ds.*, d.user_id as donor_user_id, c.name as charity_name, c.id as charity_id,
                     m.name as module_name
              FROM donation_sessions ds
              JOIN donors d ON ds.donor_id = d.id
              JOIN charities c ON ds.charity_id = c.id
              JOIN modules m ON ds.module_id = m.module_id
              WHERE ds.donor_id = ? AND ds.status = 'active' 
              AND ds.expires_at > NOW()";
    
    $params = [$donor_id];
    
    if (!empty($module_id)) {
        $query .= " AND ds.module_id = ?";
        $params[] = $module_id;
    }
    
    $query .= " ORDER BY ds.started_at DESC LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session) {
        echo json_encode([
            'success' => true,
            'session' => $session
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'session' => null,
            'message' => 'No active session found'
        ]);
    }
}
?>