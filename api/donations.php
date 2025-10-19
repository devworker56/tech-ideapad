<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Get input data based on method
if ($method == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST; // Fallback to form data
    }
} else {
    $input = $_GET;
}

// Enable CORS for React Native app
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    switch($action) {
        case 'record_donation':
            if ($method == 'POST') {
                recordDonation($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'stats':
            if ($method == 'GET') {
                getDonationStats($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'history':
            if ($method == 'GET') {
                getDonationHistory($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'tax_receipts':
            if ($method == 'GET') {
                getTaxReceipts($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'charity_donations':
            if ($method == 'GET') {
                getCharityDonations($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'recent_donations':
            if ($method == 'GET') {
                getRecentDonations($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'session_donations':
            if ($method == 'GET') {
                getSessionDonations($db, $input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Record a donation from Module MDVA - WITH SESSION VALIDATION
 */
function recordDonation($db, $data) {
    error_log("Recording donation: " . json_encode($data));
    
    $module_id = $data['module_id'] ?? '';
    $amount = $data['amount'] ?? 0;
    $coin_count = $data['coin_count'] ?? 0;
    $session_id = $data['session_id'] ?? '';
    $session_token = $data['session_token'] ?? '';
    
    if (empty($module_id) || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: module_id and amount > 0 required']);
        return;
    }
    
    try {
        // Verify active session exists for this module
        $session_query = "SELECT ds.*, d.id as donor_id, d.user_id as donor_user_id, 
                                 c.id as charity_id, c.name as charity_name
                          FROM donation_sessions ds
                          JOIN donors d ON ds.donor_id = d.id
                          JOIN charities c ON ds.charity_id = c.id
                          WHERE ds.module_id = ? AND ds.status = 'active' 
                          AND ds.expires_at > NOW()
                          ORDER BY ds.started_at DESC LIMIT 1";
        
        $session_stmt = $db->prepare($session_query);
        $session_stmt->execute([$module_id]);
        $session = $session_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No active donation session for this module']);
            return;
        }
        
        $donor_id = $session['donor_id'];
        $charity_id = $session['charity_id'];
        $session_id = $session['id'];
        
        error_log("Active session found: donor_id=$donor_id, charity_id=$charity_id, session_id=$session_id");
        
        // Use stored procedure to record donation with proper validation
        $stmt = $db->prepare("CALL RecordDonationWithStats(?, ?, ?, ?, ?, ?)");
        $stmt->execute([$donor_id, $charity_id, $amount, $coin_count, $module_id, $session_id]);
        
        $donation_id = $db->lastInsertId();
        error_log("Donation recorded successfully with ID: " . $donation_id);
        
        // Get updated session info
        $session_query = "SELECT total_amount, total_coins FROM donation_sessions WHERE id = ?";
        $session_stmt = $db->prepare($session_query);
        $session_stmt->execute([$session_id]);
        $updated_session = $session_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Notify via Pusher for real-time updates
        notify_pusher('donation_received', [
            'donation_id' => $donation_id,
            'donor_id' => $donor_id,
            'donor_user_id' => $session['donor_user_id'],
            'charity_id' => $charity_id,
            'charity_name' => $session['charity_name'],
            'amount' => $amount,
            'session_id' => $session_id,
            'module_id' => $module_id,
            'session_total' => $updated_session['total_amount'],
            'timestamp' => date('Y-m-d H:i:s')
        ], "user_" . $session['donor_user_id']);
        
        $response = [
            'success' => true, 
            'message' => 'Donation recorded successfully',
            'donation_id' => $donation_id,
            'donor_id' => $session['donor_user_id'],
            'charity_id' => $charity_id,
            'charity_name' => $session['charity_name'],
            'amount' => $amount,
            'module_id' => $module_id,
            'session_id' => $session_id,
            'session_total' => $updated_session['total_amount'],
            'session_coins' => $updated_session['total_coins']
        ];
        
        error_log("Sending success response: " . json_encode($response));
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(500);
        error_log("Record donation exception: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}

/**
 * Get donation statistics for a donor
 */
function getDonationStats($db, $data) {
    $donor_id = $data['donor_id'] ?? '';
    
    if (empty($donor_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Donor ID required']);
        return;
    }
    
    $query = "SELECT 
                SUM(amount) as total_donated, 
                COUNT(*) as donation_count,
                AVG(amount) as average_donation,
                MAX(amount) as largest_donation,
                MIN(amount) as smallest_donation,
                MAX(created_at) as last_donation_date
              FROM donations 
              WHERE donor_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$donor_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get charity distribution (based on actual donations, not preferences)
    $query = "SELECT 
                c.name as charity_name,
                c.id as charity_id,
                SUM(d.amount) as total_donated,
                COUNT(*) as donation_count
              FROM donations d
              JOIN charities c ON d.charity_id = c.id
              WHERE d.donor_id = ?
              GROUP BY d.charity_id
              ORDER BY total_donated DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$donor_id]);
    $charity_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'stats' => $stats ?: [
            'total_donated' => 0,
            'donation_count' => 0,
            'average_donation' => 0,
            'largest_donation' => 0,
            'smallest_donation' => 0,
            'last_donation_date' => null
        ],
        'charity_distribution' => $charity_distribution
    ]);
}

/**
 * Get donation history for a donor
 */
function getDonationHistory($db, $data) {
    $donor_id = $data['donor_id'] ?? '';
    
    if (empty($donor_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Donor ID required']);
        return;
    }
    
    try {
        $query = "SELECT 
                    d.id,
                    d.amount,
                    d.created_at,
                    d.module_id,
                    d.session_id,
                    c.name as charity_name,
                    c.id as charity_id,
                    ds.started_at as session_started
                  FROM donations d
                  JOIN charities c ON d.charity_id = c.id
                  JOIN donation_sessions ds ON d.session_id = ds.id
                  WHERE d.donor_id = ?
                  ORDER BY d.created_at DESC
                  LIMIT 50";
        $stmt = $db->prepare($query);
        $stmt->execute([$donor_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'history' => $history,
            'count' => count($history)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log("Donation history error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to load donation history: ' . $e->getMessage()]);
    }
}

/**
 * Get donations for a specific session
 */
function getSessionDonations($db, $data) {
    $session_id = $data['session_id'] ?? '';
    $donor_id = $data['donor_id'] ?? '';
    
    if (empty($session_id) || empty($donor_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Session ID and Donor ID required']);
        return;
    }
    
    try {
        $query = "SELECT 
                    d.id,
                    d.amount,
                    d.coin_count,
                    d.created_at,
                    c.name as charity_name,
                    m.name as module_name
                  FROM donations d
                  JOIN charities c ON d.charity_id = c.id
                  JOIN modules m ON d.module_id = m.module_id
                  WHERE d.session_id = ? AND d.donor_id = ?
                  ORDER BY d.created_at ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([$session_id, $donor_id]);
        $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get session info
        $session_query = "SELECT ds.*, c.name as charity_name 
                         FROM donation_sessions ds
                         JOIN charities c ON ds.charity_id = c.id
                         WHERE ds.id = ? AND ds.donor_id = ?";
        $session_stmt = $db->prepare($session_query);
        $session_stmt->execute([$session_id, $donor_id]);
        $session = $session_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'donations' => $donations,
            'session' => $session,
            'count' => count($donations)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log("Session donations error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to load session donations: ' . $e->getMessage()]);
    }
}

// ... (keep other existing functions like getTaxReceipts, getCharityDonations, getRecentDonations the same)
// These functions don't need changes for per-session charity selection

/**
 * Get tax receipt data for a donor
 */
function getTaxReceipts($db, $data) {
    $donor_id = $data['donor_id'] ?? '';
    $year = $data['year'] ?? date('Y');
    
    if (empty($donor_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Donor ID required']);
        return;
    }
    
    $receipt_data = generate_tax_receipt_data($donor_id, $year, $db);
    
    echo json_encode([
        'success' => true, 
        'receipt' => $receipt_data,
        'donor_info' => getDonorInfo($donor_id, $db)
    ]);
}

/**
 * Get donations for a charity
 */
function getCharityDonations($db, $data) {
    $charity_id = $data['charity_id'] ?? '';
    $limit = $data['limit'] ?? 50;
    $offset = $data['offset'] ?? 0;
    
    if (empty($charity_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Charity ID required']);
        return;
    }
    
    $query = "SELECT 
                d.id,
                d.amount,
                d.created_at,
                d.module_id,
                do.user_id as donor_id,
                ds.id as session_id
              FROM donations d
              JOIN donors do ON d.donor_id = do.id
              JOIN donation_sessions ds ON d.session_id = ds.id
              WHERE d.charity_id = ?
              ORDER BY d.created_at DESC
              LIMIT ? OFFSET ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$charity_id, $limit, $offset]);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count and amount for pagination
    $query = "SELECT COUNT(*) as total, SUM(amount) as total_amount 
              FROM donations 
              WHERE charity_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$charity_id]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'donations' => $donations,
        'pagination' => [
            'total' => $totals['total'],
            'total_amount' => $totals['total_amount'],
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
}

/**
 * Get recent donations across the system
 */
function getRecentDonations($db, $data) {
    $limit = $data['limit'] ?? 10;
    
    $query = "SELECT 
                d.id,
                d.amount,
                d.created_at,
                do.user_id as donor_id,
                c.name as charity_name,
                ds.id as session_id
              FROM donations d
              JOIN donors do ON d.donor_id = do.id
              JOIN charities c ON d.charity_id = c.id
              JOIN donation_sessions ds ON d.session_id = ds.id
              ORDER BY d.created_at DESC
              LIMIT ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$limit]);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'recent_donations' => $donations
    ]);
}

/**
 * Helper function to get donor info
 */
function getDonorInfo($donor_id, $db) {
    $query = "SELECT user_id, email, created_at FROM donors WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$donor_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>