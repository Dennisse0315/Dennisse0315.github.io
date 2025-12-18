<?php
$pageTitle = 'Manage Bookings';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';

        $allowedStatuses = ['approved', 'rejected', 'completed', 'cancelled'];

        if ($bookingId && in_array($newStatus, $allowedStatuses)) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $bookingId]);

            // If approved, update vehicle status to rented
            if ($newStatus === 'approved') {
                $bookingStmt = $pdo->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
                $bookingStmt->execute([$bookingId]);
                $booking = $bookingStmt->fetch();

                if ($booking) {
                    $updateVehicle = $pdo->prepare("UPDATE vehicles SET status = 'rented' WHERE id = ?");
                    $updateVehicle->execute([$booking['vehicle_id']]);
                }
            }

            // If completed or cancelled, set vehicle back to available
            if ($newStatus === 'completed' || $newStatus === 'cancelled' || $newStatus === 'rejected') {
                $bookingStmt = $pdo->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
                $bookingStmt->execute([$bookingId]);
                $booking = $bookingStmt->fetch();

                if ($booking) {
                    $updateVehicle = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?");
                    $updateVehicle->execute([$booking['vehicle_id']]);
                }
            }

            redirect('/Rental/admin/bookings.php', 'Booking status updated successfully.', 'success');
        }
    }
}

// Filter by status
$statusFilter = $_GET['status'] ?? '';
$sql = "
    SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
           v.name as vehicle_name, v.type as vehicle_type, v.image as vehicle_image
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
";

$params = [];
if ($statusFilter) {
    $sql .= " WHERE b.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get counts for each status
$counts = [];
$countStmt = $pdo->query("
    SELECT status, COUNT(*) as count
    FROM bookings
    GROUP BY status
");
while ($row = $countStmt->fetch()) {
    $counts[$row['status']] = $row['count'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-header">
    <h1>Manage Bookings</h1>
    <a href="/Rental/auth/logout.php" class="btn btn-outline">Logout</a>
</div>

<nav class="admin-nav">
    <a href="/Rental/admin/">Dashboard</a>
    <a href="/Rental/admin/vehicles.php">Vehicles</a>
    <a href="/Rental/admin/bookings.php" class="active">Bookings</a>
</nav>

<!-- Status Filter -->
<div class="mb-3 d-flex gap-1" style="flex-wrap: wrap;">
    <a href="/Rental/admin/bookings.php" class="btn <?= !$statusFilter ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        All (<?= array_sum($counts) ?>)
    </a>
    <a href="?status=pending" class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Pending (<?= $counts['pending'] ?? 0 ?>)
    </a>
    <a href="?status=approved" class="btn <?= $statusFilter === 'approved' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Approved (<?= $counts['approved'] ?? 0 ?>)
    </a>
    <a href="?status=completed" class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Completed (<?= $counts['completed'] ?? 0 ?>)
    </a>
    <a href="?status=rejected" class="btn <?= $statusFilter === 'rejected' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Rejected (<?= $counts['rejected'] ?? 0 ?>)
    </a>
    <a href="?status=cancelled" class="btn <?= $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Cancelled (<?= $counts['cancelled'] ?? 0 ?>)
    </a>
</div>

<?php if (empty($bookings)): ?>
    <div class="alert alert-info">No bookings found.</div>
<?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle</th>
                    <th>Customer</th>
                    <th>Dates</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>#<?= $booking['id'] ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="/Rental/<?= e($booking['vehicle_image']) ?>"
                                     style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                <div>
                                    <strong><?= e($booking['vehicle_name']) ?></strong>
                                    <br><small class="text-muted"><?= e($booking['vehicle_type']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?= e($booking['customer_name']) ?></strong>
                            <br><small><?= e($booking['customer_email']) ?></small>
                            <?php if ($booking['customer_phone']): ?>
                                <br><small><?= e($booking['customer_phone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= formatDate($booking['start_date']) ?><br>
                            to <?= formatDate($booking['end_date']) ?>
                            <br><small class="text-muted"><?= calculateDays($booking['start_date'], $booking['end_date']) ?> days</small>
                        </td>
                        <td><strong><?= formatPrice($booking['total_price']) ?></strong></td>
                        <td><?= statusBadge($booking['status']) ?></td>
                        <td>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <input type="hidden" name="new_status" value="approved">
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <input type="hidden" name="new_status" value="rejected">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            data-confirm="Are you sure you want to reject this booking?">Reject</button>
                                </form>
                            <?php elseif ($booking['status'] === 'approved'): ?>
                                <form method="POST" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <input type="hidden" name="new_status" value="completed">
                                    <button type="submit" class="btn btn-sm btn-success">Mark Complete</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
