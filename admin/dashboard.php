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
                <h5 class="mb-0">QR Code Generation & Printing</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>Generate QR Code</h6>
                                <form id="qrForm">
                                    <div class="mb-3">
                                        <label for="qr_data" class="form-label">QR Code Content</label>
                                        <input type="text" class="form-control" id="qr_data" 
                                               placeholder="Enter text or numbers" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="qr_size" class="form-label">Size</label>
                                        <select class="form-control" id="qr_size">
                                            <option value="3">Small</option>
                                            <option value="5" selected>Medium</option>
                                            <option value="8">Large</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-qrcode"></i> Generate QR Code
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>QR Code Preview & Printing</h6>
                                <div id="qrPreview" class="text-center mb-3" style="min-height: 150px; border: 1px dashed #ccc; padding: 20px;">
                                    <p class="text-muted">QR code will appear here</p>
                                </div>
                                <button id="printQR" class="btn btn-success w-100" disabled onclick="printQRCode()">
                                    <i class="fas fa-print"></i> Print QR Code
                                </button>
                                <a href="generate_all_qr_codes.php" class="btn btn-outline-primary mt-2 w-100">
                                    <i class="fas fa-qrcode"></i> Advanced QR Code Generator
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle QR code form submission
document.getElementById('qrForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const qrData = document.getElementById('qr_data').value;
    const qrSize = document.getElementById('qr_size').value;
    
    if (!qrData) {
        alert('Please enter some text for the QR code');
        return;
    }
    
    // Show loading
    document.getElementById('qrPreview').innerHTML = '<p>Generating QR code...</p>';
    
    // Use simple form data
    const formData = new FormData();
    formData.append('data', qrData);
    formData.append('type', 'QRCODE');
    
    fetch('generate_barcode.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('qrPreview').innerHTML = data.html;
            document.getElementById('printQR').disabled = false;
            window.currentQRCode = {
                url: data.barcode_url,
                data: data.code_data
            };
        } else {
            alert('Error: ' + data.message);
            document.getElementById('qrPreview').innerHTML = '<p class="text-danger">Error: ' + data.message + '</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating QR code');
        document.getElementById('qrPreview').innerHTML = '<p class="text-danger">Network error</p>';
    });
});

function printQRCode() {
    if (!window.currentQRCode) {
        alert('No QR code to print');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print QR Code</title>
            <style>
                body { 
                    margin: 0; 
                    padding: 20px; 
                    display: flex; 
                    justify-content: center; 
                    align-items: center;
                    min-height: 100vh;
                    font-family: Arial, sans-serif;
                }
                .qr-container {
                    text-align: center;
                    border: 1px solid #000;
                    padding: 20px;
                    max-width: 300px;
                    margin: 0 auto;
                }
                .qr-container img {
                    max-width: 100%;
                    height: auto;
                }
                @media print {
                    body { margin: 0; padding: 0; }
                    .qr-container { border: none; }
                }
            </style>
        </head>
        <body>
            <div class="qr-container">
                <img src="${window.currentQRCode.url}" alt="QR Code">
                <div style="margin-top: 10px; font-size: 14px; font-weight: bold;">${window.currentQRCode.data}</div>
                <div style="margin-top: 5px; font-size: 10px; color: #666;">
                    MDVA System • ${new Date().toLocaleDateString()}
                </div>
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() {
                        window.close();
                    }, 1000);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>
<?php include '../includes/footer.php'; ?>