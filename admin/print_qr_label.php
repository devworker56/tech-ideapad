<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$module_id = $_GET['module_id'] ?? '';

if (empty($module_id)) {
    die("Module ID required");
}

// Get module details
$query = "SELECT m.*, l.name as location_name, l.address, l.city, l.state 
          FROM modules m 
          LEFT JOIN module_locations ml ON m.id = ml.module_id AND ml.status = 'active'
          LEFT JOIN locations l ON ml.location_id = l.id 
          WHERE m.module_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$module_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$module) {
    die("Module not found");
}

$qr_file = "../qr_codes/mdva_module_" . $module_id . ".png";
if (!file_exists($qr_file)) {
    die("QR code not found for this module");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print MDVA Module Label</title>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
            .label-container { 
                border: 2px solid #000; 
                padding: 10px; 
                margin: 0;
                width: 100mm;
                height: 150mm;
                page-break-after: always;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        .label-container {
            border: 2px solid #333;
            padding: 15px;
            margin: 10px auto;
            width: 100mm;
            height: 150mm;
            background: white;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .qr-code {
            text-align: center;
            margin: 10px 0;
        }
        
        .qr-code img {
            max-width: 100%;
            height: auto;
        }
        
        .module-info {
            font-size: 12px;
            margin-top: 10px;
        }
        
        .module-info strong {
            display: block;
            margin-top: 5px;
        }
        
        .instructions {
            font-size: 10px;
            margin-top: 15px;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Print Label</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="label-container">
        <div class="header">
            <h2 style="margin: 0; color: #2196F3;">MDVA</h2>
            <p style="margin: 0; font-size: 14px;">Micro-Dons Vérifiables et Attribués</p>
        </div>
        
        <div class="qr-code">
            <img src="<?php echo $qr_file; ?>" alt="QR Code">
        </div>
        
        <div class="module-info">
            <strong>Module ID:</strong> <?php echo htmlspecialchars($module_id); ?>
            <strong>Name:</strong> <?php echo htmlspecialchars($module['name']); ?>
            <strong>Location:</strong> 
            <?php 
            if ($module['location_name']) {
                echo htmlspecialchars($module['location_name']) . "<br>";
                echo htmlspecialchars($module['address']) . "<br>";
                echo htmlspecialchars($module['city']) . ", " . htmlspecialchars($module['state']);
            } else {
                echo htmlspecialchars($module['location']);
            }
            ?>
            <strong>Coin Value:</strong> $<?php echo number_format($module['coin_value'], 2); ?> per coin
            <strong>Installation Date:</strong> <?php echo date('Y-m-d', strtotime($module['created_at'])); ?>
        </div>
        
        <div class="instructions">
            <strong>Instructions:</strong><br>
            1. Scan QR code with MDVA Donor App<br>
            2. Select your preferred charity<br>
            3. Insert coins to donate<br>
            4. Receive instant tax receipt
        </div>
        
        <div class="footer">
            MDVA System - Verified & Attributed Micro-Donations<br>
            <?php echo date('Y-m-d'); ?>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Optional: uncomment to auto-print
            // window.print();
        };
    </script>
</body>
</html>