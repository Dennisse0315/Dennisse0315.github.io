<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$vehicleId = (int)($_GET['id'] ?? 0);

if (!$vehicleId) {
    redirect('/Rental/', 'Vehicle not found.', 'danger');
}

// Get vehicle details
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    redirect('/Rental/', 'Vehicle not found.', 'danger');
}

$pageTitle = $vehicle['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="vehicle-detail">
    <img src="/Rental/<?= e($vehicle['image']) ?>" alt="<?= e($vehicle['name']) ?>" class="vehicle-detail-img">

    <div class="vehicle-detail-content">
        <div class="vehicle-detail-meta">
            <span><?= e($vehicle['type']) ?></span>
            <span><?= e($vehicle['brand']) ?></span>
            <span><?= e($vehicle['model']) ?></span>
            <?= statusBadge($vehicle['status']) ?>
        </div>

        <h1><?= e($vehicle['name']) ?></h1>

        <div class="vehicle-detail-price">
            <?= formatPrice($vehicle['price_per_day']) ?>
            <span>/ day</span>
        </div>

        <p class="vehicle-detail-desc"><?= nl2br(e($vehicle['description'])) ?></p>

        <?php if ($vehicle['status'] === 'available'): ?>
            <?php if (isLoggedIn()): ?>
                <a href="/Rental/booking.php?vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-primary btn-lg">
                    Book This Vehicle
                </a>
            <?php else: ?>
                <div class="alert alert-info">
                    Please <a href="/Rental/auth/login.php">login</a> or <a href="/Rental/auth/register.php">register</a> to book this vehicle.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-warning">
                This vehicle is currently not available for booking.
            </div>
        <?php endif; ?>

        <a href="/Rental/" class="btn btn-outline mt-2">Back to Catalog</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
