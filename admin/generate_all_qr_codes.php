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

// Handle form submissions
if (isset($_POST['generate_single'])) {
    $module_id = $_POST['module_id'] ?? '';
    $barcode_data = $_POST['barcode_data'] ?? '';
    $type = $_POST['type'] ?? 'QRCODE';
    
    if (!empty($module_id)) {
        // Generate QR code for module
        $module = getModuleDetails($module_id, $db);
        if ($module) {
            $qr_file = generateModuleQRCode($module_id, $module['name'], $module['location']);
            $success_message = "QR code generated for module: " . htmlspecialchars($module_id);
            $preview_file = $qr_file;
            $preview_data = $module_id;
        }
    } elseif (!empty($barcode_data)) {
        // Use AJAX to generate barcode
        $preview_file = ''; // Will be set via AJAX
        $preview_data = $barcode_data;
    }
}

if (isset($_POST['generate_all'])) {
    $generated = generateAllModuleQRCodes($db);
    $success_message = "Generated " . count($generated) . " QR codes";
}

// Get all modules for dropdown
$modules = getActiveModules($db);

function getModuleDetails($module_id, $db) {
    $query = "SELECT * FROM modules WHERE module_id = ? AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$module_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getActiveModules($db) {
    $query = "SELECT module_id, name FROM modules WHERE status = 'active' ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Generate QR Codes & Barcodes</h2>
    
    <?php if(isset($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Single Code Generation -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Generate Single Code</h5>
                </div>
                <div class="card-body">
                    <form id="barcodeForm" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Generate for Module</label>
                            <select name="module_id" id="module_id" class="form-control">
                                <option value="">-- Select Module --</option>
                                <?php foreach($modules as $module): ?>
                                <option value="<?php echo htmlspecialchars($module['module_id']); ?>">
                                    <?php echo htmlspecialchars($module['name']); ?> (<?php echo htmlspecialchars($module['module_id']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="text-center my-3">OR</div>
                        
                        <div class="mb-3">
                            <label class="form-label">Custom Barcode/QR Code</label>
                            <input type="text" name="barcode_data" id="barcode_data" class="form-control" 
                                   placeholder="Enter custom text or numbers">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Code Type</label>
                            <select name="type" id="barcode_type" class="form-control">
                                <option value="QRCODE">QR Code</option>
                                <option value="CODE128">Barcode (CODE128)</option>
                                <option value="CODE39">Barcode (CODE39)</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="generate_single" class="btn btn-primary">
                            <i class="fas fa-qrcode"></i> Generate Code
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Preview Section -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Preview & Print</h5>
                </div>
                <div class="card-body">
                    <div id="codePreview" class="text-center mb-3" style="min-height: 200px; border: 1px dashed #ccc; padding: 20px; display: flex; align-items: center; justify-content: center;">
                        <p class="text-muted">Generated code will appear here</p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button id="printSingle" class="btn btn-success" disabled onclick="printSingleCode()">
                            <i class="fas fa-print"></i> Print This Code
                        </button>
                        <a href="print_bulk_labels.php" class="btn btn-outline-primary">
                            <i class="fas fa-tags"></i> Print Multiple Labels
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bulk Generation -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Bulk QR Code Generation</h5>
                </div>
                <div class="card-body">
                    <p>This will generate QR codes for all active modules in the system.</p>
                    <p><strong>Note:</strong> Existing QR codes with the same module ID will be overwritten.</p>
                    
                    <form method="POST">
                        <button type="submit" name="generate_all" class="btn btn-primary" onclick="return confirm('Generate QR codes for all active modules?')">
                            <i class="fas fa-qrcode"></i> Generate All QR Codes
                        </button>
                    </form>
                    
                    <hr>
                    
                    <h6>Printing Specifications:</h6>
                    <ul>
                        <li><strong>QR Codes:</strong> 2x2 inches minimum for reliable scanning</li>
                        <li><strong>Barcodes:</strong> Adjust height based on scanning distance</li>
                        <li><strong>Material:</strong> Use adhesive vinyl for outdoor modules</li>
                        <li><strong>Placement:</strong> Visible, easily scannable location</li>
                    </ul>
                    
                    <div class="alert alert-info">
                        <small>
                            <strong>Printing Tip:</strong> Use the browser's print dialog (Ctrl+P) and select your preferred printer. 
                            For label sheets, use the "Print Multiple Labels" feature.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle form submission with AJAX for preview
document.getElementById('barcodeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const moduleId = document.getElementById('module_id').value;
    const barcodeData = document.getElementById('barcode_data').value;
    const barcodeType = document.getElementById('barcode_type').value;
    
    // If module is selected, use regular form submission for QR codes
    if (moduleId) {
        this.submit();
        return;
    }
    
    // If custom data, use AJAX
    if (barcodeData) {
        const formData = new FormData();
        formData.append('data', barcodeData);
        formData.append('type', barcodeType);
        formData.append('width', 2);
        formData.append('height', 1);
        
        fetch('generate_barcode.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('codePreview').innerHTML = data.html;
                document.getElementById('printSingle').disabled = false;
                window.currentCode = {
                    url: data.barcode_url,
                    data: barcodeData,
                    type: barcodeType
                };
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error generating code');
        });
    } else {
        alert('Please enter some data or select a module');
    }
});

function printSingleCode() {
    if (!window.currentCode) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print ${window.currentCode.type}</title>
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
                .code-container {
                    text-align: center;
                    border: 1px solid #000;
                    padding: 20px;
                    max-width: 300px;
                    margin: 0 auto;
                }
                .code-container img {
                    max-width: 100%;
                    height: auto;
                }
                @media print {
                    body { margin: 0; padding: 0; }
                    .code-container { border: none; }
                }
            </style>
        </head>
        <body>
            <div class="code-container">
                <img src="${window.currentCode.url}" alt="${window.currentCode.type}">
                <div style="margin-top: 10px; font-size: 14px; font-weight: bold;">${window.currentCode.data}</div>
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