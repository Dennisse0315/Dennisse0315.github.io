<?php
$pageTitle = 'My Bookings';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $bookingId = (int)$_POST['booking_id'];

        // Only allow cancelling own bookings that are pending
        $stmt = $pdo->prepare("
            UPDATE bookings
            SET status = 'cancelled'
            WHERE id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$bookingId, $_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            redirect('/Rental/my-bookings.php', 'Booking cancelled successfully.', 'success');
        } else {
            redirect('/Rental/my-bookings.php', 'Unable to cancel this booking.', 'danger');
        }
    }
}

// Get user's bookings
$stmt = $pdo->prepare("
    SELECT b.*, v.name as vehicle_name, v.image as vehicle_image, v.type as vehicle_type
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>My Bookings</h1>
    <p>View and manage your vehicle rental bookings</p>
</div>

<?php if (empty($bookings)): ?>
    <div class="alert alert-info text-center">
        <p>You haven't made any bookings yet.</p>
        <a href="/Rental/" class="btn btn-primary mt-2">Browse Vehicles</a>
    </div>
<?php else: ?>
    <?php foreach ($bookings as $booking): ?>
        <div class="booking-card">
            <img src="/Rental/<?= e($booking['vehicle_image']) ?>" alt="<?= e($booking['vehicle_name']) ?>" class="booking-card-img">

            <div class="booking-card-info">
                <h3><?= e($booking['vehicle_name']) ?></h3>
                <p class="text-muted"><?= e($booking['vehicle_type']) ?></p>
                <p class="booking-card-dates">
                    <?= formatDate($booking['start_date']) ?> - <?= formatDate($booking['end_date']) ?>
                    (<?= calculateDays($booking['start_date'], $booking['end_date']) ?> days)
                </p>
                <div class="mt-1">
                    <?= statusBadge($booking['status']) ?>
                </div>
            </div>

            <div class="booking-card-actions">
                <div class="booking-card-price"><?= formatPrice($booking['total_price']) ?></div>

                <?php if ($booking['status'] === 'pending'): ?>
                    <form method="POST" style="display: inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                        <button type="submit" name="cancel_booking" class="btn btn-danger btn-sm"
                                data-confirm="Are you sure you want to cancel this booking?">
                            Cancel
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
