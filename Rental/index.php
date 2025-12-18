<?php
$pageTitle = 'Home';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Get filter parameters
$typeFilter = $_GET['type'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM vehicles WHERE status = 'available'";
$params = [];

if ($typeFilter) {
    $sql .= " AND type = ?";
    $params[] = $typeFilter;
}

if ($searchQuery) {
    $sql .= " AND (name LIKE ? OR brand LIKE ? OR model LIKE ? OR description LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Get unique vehicle types for filter
$typeStmt = $pdo->query("SELECT DISTINCT type FROM vehicles ORDER BY type");
$types = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Find Your Perfect Ride</h1>
    <p>Browse our collection of quality vehicles available for rent</p>
</div>

<!-- Search and Filter -->
<form method="GET" class="mb-3" style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
    <input type="text" name="search" class="form-control" placeholder="Search vehicles..."
           value="<?= e($searchQuery) ?>" style="max-width: 300px;">

    <select name="type" class="form-control" style="max-width: 200px;">
        <option value="">All Types</option>
        <?php foreach ($types as $type): ?>
            <option value="<?= e($type) ?>" <?= $typeFilter === $type ? 'selected' : '' ?>>
                <?= e($type) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($searchQuery || $typeFilter): ?>
        <a href="/Rental/" class="btn btn-outline">Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($vehicles)): ?>
    <div class="alert alert-info text-center">
        <p>No vehicles found matching your criteria.</p>
        <a href="/Rental/" class="btn btn-primary mt-2">View All Vehicles</a>
    </div>
<?php else: ?>
    <div class="vehicle-grid">
        <?php foreach ($vehicles as $vehicle): ?>
            <div class="card vehicle-card">
                <img src="/Rental/<?= e($vehicle['image']) ?>" alt="<?= e($vehicle['name']) ?>" class="card-img">
                <div class="card-body">
                    <div class="card-meta">
                        <span><?= e($vehicle['type']) ?></span>
                        <span><?= e($vehicle['brand']) ?></span>
                    </div>
                    <h3 class="card-title"><?= e($vehicle['name']) ?></h3>
                    <p class="card-text"><?= e(substr($vehicle['description'], 0, 80)) ?>...</p>
                    <div class="card-footer">
                        <div class="card-price">
                            <?= formatPrice($vehicle['price_per_day']) ?>
                            <span>/ day</span>
                        </div>
                        <a href="/Rental/vehicle.php?id=<?= $vehicle['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
