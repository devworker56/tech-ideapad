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
                <h5 class="mb-0">Station QR Code Generator</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>Station Information</h6>
                                <form id="stationForm">
                                    <div class="mb-3">
                                        <label for="stationId" class="form-label">Station ID *</label>
                                        <input type="text" class="form-control" id="stationId" 
                                               placeholder="Enter station ID" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="locationName" class="form-label">Location Name *</label>
                                        <input type="text" class="form-control" id="locationName" 
                                               placeholder="e.g., Montreal Central Mall" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address *</label>
                                        <input type="text" class="form-control" id="address" 
                                               placeholder="Street address" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="city" class="form-label">City *</label>
                                                <input type="text" class="form-control" id="city" 
                                                       placeholder="City" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="province" class="form-label">Province *</label>
                                                <input type="text" class="form-control" id="province" 
                                                       placeholder="Province" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="postalCode" class="form-label">Postal Code *</label>
                                        <input type="text" class="form-control" id="postalCode" 
                                               placeholder="A1A 1A1" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-qrcode"></i> Generate Station QR Code
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>QR Code Preview & Printing</h6>
                                <div id="qrPreview" class="text-center mb-3" style="min-height: 200px; border: 1px dashed #ccc; padding: 20px;">
                                    <p class="text-muted">Station QR code will appear here</p>
                                </div>
                                
                                <div id="stationInfo" class="mb-3" style="display: none;">
                                    <h6>Station Details:</h6>
                                    <div id="stationDetails" class="small"></div>
                                </div>
                                
                                <button id="printQR" class="btn btn-success w-100" disabled onclick="printStationQRCode()">
                                    <i class="fas fa-print"></i> Print Station QR Code
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
// Handle station form submission
document.getElementById('stationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Collect all form data
    const stationData = {
        stationId: document.getElementById('stationId').value,
        locationName: document.getElementById('locationName').value,
        address: document.getElementById('address').value,
        city: document.getElementById('city').value,
        province: document.getElementById('province').value,
        postalCode: document.getElementById('postalCode').value
    };
    
    // Validate required fields
    for (const key in stationData) {
        if (!stationData[key].trim()) {
            alert('Please fill in all required fields');
            return;
        }
    }
    
    // Show loading
    document.getElementById('qrPreview').innerHTML = '<p>Generating station QR code...</p>';
    document.getElementById('stationInfo').style.display = 'none';
    
    // Send data to generate QR code
    const formData = new FormData();
    formData.append('data', JSON.stringify(stationData));
    formData.append('type', 'QRCODE');
    
    fetch('generate_barcode.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Display QR code
            document.getElementById('qrPreview').innerHTML = data.html;
            
            // Display station details
            document.getElementById('stationDetails').innerHTML = `
                <strong>Station ID:</strong> ${stationData.stationId}<br>
                <strong>Location:</strong> ${stationData.locationName}<br>
                <strong>Address:</strong> ${stationData.address}<br>
                <strong>City:</strong> ${stationData.city}<br>
                <strong>Province:</strong> ${stationData.province}<br>
                <strong>Postal Code:</strong> ${stationData.postalCode}
            `;
            document.getElementById('stationInfo').style.display = 'block';
            
            // Enable print button
            document.getElementById('printQR').disabled = false;
            
            // Store data for printing
            window.currentStationQR = {
                url: data.barcode_url,
                stationData: stationData
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

function printStationQRCode() {
    if (!window.currentStationQR) {
        alert('No QR code to print');
        return;
    }
    
    const station = window.currentStationQR.stationData;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Station QR Code - ${station.stationId}</title>
            <style>
                body { 
                    margin: 0; 
                    padding: 20px; 
                    font-family: Arial, sans-serif;
                }
                .label-container {
                    border: 2px solid #000;
                    padding: 15px;
                    max-width: 300px;
                    margin: 0 auto;
                }
                .qr-code {
                    text-align: center;
                    margin-bottom: 15px;
                }
                .qr-code img {
                    max-width: 100%;
                    height: auto;
                }
                .station-info {
                    font-size: 12px;
                    line-height: 1.4;
                }
                .station-info strong {
                    display: block;
                    margin-top: 5px;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                    margin-bottom: 10px;
                }
                @media print {
                    body { margin: 0; padding: 10px; }
                    .label-container { border: 1px solid #000; }
                }
            </style>
        </head>
        <body>
            <div class="label-container">
                <div class="header">
                    <h3 style="margin: 0; color: #2196F3;">MDVA</h3>
                    <p style="margin: 0; font-size: 14px;">Donation Station</p>
                </div>
                
                <div class="qr-code">
                    <img src="${window.currentStationQR.url}" alt="Station QR Code">
                </div>
                
                <div class="station-info">
                    <strong>Station ID:</strong> ${station.stationId}
                    <strong>Location:</strong> ${station.locationName}
                    <strong>Address:</strong> ${station.address}
                    <strong>City:</strong> ${station.city}, ${station.province}
                    <strong>Postal Code:</strong> ${station.postalCode}
                </div>
                
                <div style="text-align: center; margin-top: 10px; font-size: 10px; color: #666;">
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