<?php
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
include '../includes/header.php';

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get statistics
$query = "SELECT COUNT(*) as total_charities FROM charities";
$stmt = $db->prepare($query);
$stmt->execute();
$total_charities = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT COUNT(*) as pending_charities FROM charities WHERE approved = 0";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_charities = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT SUM(amount) as total_donations FROM donations";
$stmt = $db->prepare($query);
$stmt->execute();
$total_donations = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT COUNT(*) as total_donors FROM donors";
$stmt = $db->prepare($query);
$stmt->execute();
$total_donors = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Admin Dashboard</h2>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_charities['total_charities']; ?></h5>
                    <p class="card-text">Total Charities</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $pending_charities['pending_charities']; ?></h5>
                    <p class="card-text">Pending Approvals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">$<?php echo number_format($total_donations['total_donations'] ?? 0, 2); ?></h5>
                    <p class="card-text">Total Donations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_donors['total_donors']; ?></h5>
                    <p class="card-text">Total Donors</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Charity Management</h5>
                </div>
                <div class="card-body">
                    <a href="manage_charities.php" class="btn btn-primary">Manage Charities</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Add this to the dashboard after the existing Charity Management section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Barcode Generation & Printing</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>Generate Single Barcode</h6>
                                <form id="barcodeForm">
                                    <div class="mb-3">
                                        <label for="barcode_data" class="form-label">Barcode Content</label>
                                        <input type="text" class="form-control" id="barcode_data" 
                                               placeholder="Enter text or numbers" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="barcode_type" class="form-label">Barcode Type</label>
                                        <select class="form-control" id="barcode_type">
                                            <option value="CODE128">CODE128</option>
                                            <option value="CODE39">CODE39</option>
                                            <option value="EAN13">EAN-13</option>
                                            <option value="UPCA">UPC-A</option>
                                            <option value="QRCODE">QR Code</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="barcode_width" class="form-label">Width</label>
                                        <input type="number" class="form-control" id="barcode_width" value="2" min="1" max="5">
                                    </div>
                                    <div class="mb-3">
                                        <label for="barcode_height" class="form-label">Height</label>
                                        <input type="number" class="form-control" id="barcode_height" value="1" min="1" max="5">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Generate Barcode</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>Barcode Preview & Printing</h6>
                                <div id="barcodePreview" class="text-center mb-3" style="min-height: 150px; border: 1px dashed #ccc; padding: 20px;">
                                    <p class="text-muted">Barcode will appear here</p>
                                </div>
                                <button id="printBarcode" class="btn btn-success w-100" disabled onclick="printBarcode()">
                                    <i class="fas fa-print"></i> Print Barcode
                                </button>
                                <a href="barcode_bulk.php" class="btn btn-outline-primary mt-2 w-100">
                                    <i class="fas fa-barcode"></i> Bulk Barcode Generator
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>