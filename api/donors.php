<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

// IMPROVED INPUT HANDLING
$input = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }
} else {
    $input = $_GET;
}

// ADD CORS HEADERS FOR MOBILE APP
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ADD ERROR HANDLING FOR DATABASE
try {
    switch($action) {
        case 'register':
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                break;
            }
            
            // Generate unique user ID
            $user_id = 'DONOR_' . uniqid();
            
            // Check if email exists
            $query = "SELECT id FROM donors WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            
            if($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already registered']);
                break;
            }
            
            // Insert donor (NO selected_charity_id)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO donors (user_id, email, password) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$user_id, $email, $hashed_password])) {
                // Get the newly created donor ID
                $new_donor_id = $db->lastInsertId();
                
                // Create initial verifiable transaction for this donor
                create_initial_verifiable_transaction($new_donor_id, $user_id, $db);
                
                echo json_encode(['success' => true, 'message' => 'Registration successful']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Registration failed']);
            }
            break;
            
        case 'login':
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                break;
            }
            
            $query = "SELECT * FROM donors WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $donor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($donor && password_verify($password, $donor['password'])) {
                echo json_encode([
                    'success' => true,
                    'token' => bin2hex(random_bytes(32)),
                    'user' => [
                        'id' => $donor['id'],
                        'user_id' => $donor['user_id'],
                        'email' => $donor['email']
                        // NO selected_charity_id in response
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
            break;
            
        // REMOVED: select_charity action - charity selection is now per-session only
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("PHP Exception in donors.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ]);
}
?>