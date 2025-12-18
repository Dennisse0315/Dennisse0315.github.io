<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// Get statistics
$stats = [];

// Total vehicles
$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles");
$stats['total_vehicles'] = $stmt->fetchColumn();

// Available vehicles
$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'available'");
$stats['available_vehicles'] = $stmt->fetchColumn();

// Total bookings
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
$stats['total_bookings'] = $stmt->fetchColumn();

// Pending bookings
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$stats['pending_bookings'] = $stmt->fetchColumn();

// Total customers
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$stats['total_customers'] = $stmt->fetchColumn();

// Total revenue (approved + completed)
$stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status IN ('approved', 'completed')");
$stats['total_revenue'] = $stmt->fetchColumn();

// Recent bookings
$recentBookings = $pdo->query("
    SELECT b.*, u.name as customer_name, v.name as vehicle_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    ORDER BY b.created_at DESC
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-header">
    <h1>Admin Dashboard</h1>
    <a href="/Rental/auth/logout.php" class="btn btn-outline">Logout</a>
</div>

<nav class="admin-nav">
    <a href="/Rental/admin/" class="active">Dashboard</a>
    <a href="/Rental/admin/vehicles.php">Vehicles</a>
    <a href="/Rental/admin/bookings.php">Bookings</a>
</nav>

<div class="stats-grid">
    <div class="stat-card primary">
        <h3>Total Vehicles</h3>
        <div class="stat-value"><?= $stats['total_vehicles'] ?></div>
    </div>

    <div class="stat-card success">
        <h3>Available</h3>
        <div class="stat-value"><?= $stats['available_vehicles'] ?></div>
    </div>

    <div class="stat-card warning">
        <h3>Pending Bookings</h3>
        <div class="stat-value"><?= $stats['pending_bookings'] ?></div>
    </div>

    <div class="stat-card">
        <h3>Total Customers</h3>
        <div class="stat-value"><?= $stats['total_customers'] ?></div>
    </div>

    <div class="stat-card">
        <h3>Total Bookings</h3>
        <div class="stat-value"><?= $stats['total_bookings'] ?></div>
    </div>

    <div class="stat-card success">
        <h3>Total Revenue</h3>
        <div class="stat-value"><?= formatPrice($stats['total_revenue']) ?></div>
    </div>
</div>

<h2 class="mb-2">Recent Bookings</h2>

<?php if (empty($recentBookings)): ?>
    <div class="alert alert-info">No bookings yet.</div>
<?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Dates</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentBookings as $booking): ?>
                    <tr>
                        <td>#<?= $booking['id'] ?></td>
                        <td><?= e($booking['customer_name']) ?></td>
                        <td><?= e($booking['vehicle_name']) ?></td>
                        <td><?= formatDate($booking['start_date']) ?> - <?= formatDate($booking['end_date']) ?></td>
                        <td><?= formatPrice($booking['total_price']) ?></td>
                        <td><?= statusBadge($booking['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="mt-2">
        <a href="/Rental/admin/bookings.php" class="btn btn-primary">View All Bookings</a>
    </p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
