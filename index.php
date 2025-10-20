<?php include 'includes/header.php'; ?>
<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get approved charities
$query = "SELECT * FROM charities WHERE approved = 1 ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$charities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total donations
$query = "SELECT SUM(amount) as total_donations FROM donations";
$stmt = $db->prepare($query);
$stmt->execute();
$total_donations = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Make a Difference with Micro-Donations</h1>
            <p class="lead">MDVA turns your spare change into verifiable donations. We consolidate your annual giving into a single tax receipt, so you can claim your credit, while ensuring charities receive fully attributed, trusted support.</p>
        </div>
    </div>

<div class="container my-5">
    <h2 class="text-center mb-4">Our Verified Charities</h2>
    <div class="row" id="charities-container">
        <?php foreach($charities as $charity): ?>
        <div class="col-md-4">
            <div class="card charity-card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($charity['name']); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars($charity['description']); ?></p>
                    <?php if($charity['website']): ?>
                    <a href="<?php echo htmlspecialchars($charity['website']); ?>" class="btn btn-outline-primary" target="_blank">
                        Visit Website
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// WebSocket connection for real-time updates
const ws = new WebSocket('ws://localhost:8080');
ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    if(data.type === 'new_charity') {
        // Reload the page to show new charity
        location.reload();
    }
};
</script>

<?php include 'includes/footer.php'; ?>