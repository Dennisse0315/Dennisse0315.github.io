<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$vehicleId = (int)($_GET['vehicle_id'] ?? 0);

if (!$vehicleId) {
    redirect('/Rental/', 'Please select a vehicle to book.', 'danger');
}

// Get vehicle details
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND status = 'available'");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    redirect('/Rental/', 'Vehicle is not available for booking.', 'danger');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';

        // Validation
        if (empty($startDate)) {
            $errors[] = 'Start date is required.';
        }

        if (empty($endDate)) {
            $errors[] = 'End date is required.';
        }

        if ($startDate && $endDate) {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $today = new DateTime('today');

            if ($start < $today) {
                $errors[] = 'Start date cannot be in the past.';
            }

            if ($end < $start) {
                $errors[] = 'End date must be after start date.';
            }

            // Check for booking conflicts
            $conflictStmt = $pdo->prepare("
                SELECT id FROM bookings
                WHERE vehicle_id = ?
                AND status IN ('pending', 'approved')
                AND (
                    (start_date <= ? AND end_date >= ?)
                    OR (start_date <= ? AND end_date >= ?)
                    OR (start_date >= ? AND end_date <= ?)
                )
            ");
            $conflictStmt->execute([
                $vehicleId,
                $startDate, $startDate,
                $endDate, $endDate,
                $startDate, $endDate
            ]);

            if ($conflictStmt->fetch()) {
                $errors[] = 'This vehicle is already booked for the selected dates.';
            }
        }

        // Create booking if no errors
        if (empty($errors)) {
            $days = calculateDays($startDate, $endDate);
            $totalPrice = $days * $vehicle['price_per_day'];

            $insertStmt = $pdo->prepare("
                INSERT INTO bookings (user_id, vehicle_id, start_date, end_date, total_price, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");

            if ($insertStmt->execute([$_SESSION['user_id'], $vehicleId, $startDate, $endDate, $totalPrice])) {
                redirect('/Rental/my-bookings.php', 'Booking submitted successfully! Please wait for approval.', 'success');
            } else {
                $errors[] = 'Failed to create booking. Please try again.';
            }
        }
    }
}

$pageTitle = 'Book ' . $vehicle['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="vehicle-detail">
    <img src="/Rental/<?= e($vehicle['image']) ?>" alt="<?= e($vehicle['name']) ?>" class="vehicle-detail-img">

    <div class="vehicle-detail-content">
        <h1>Book: <?= e($vehicle['name']) ?></h1>

        <div class="vehicle-detail-meta">
            <span><?= e($vehicle['type']) ?></span>
            <span><?= e($vehicle['brand']) ?> <?= e($vehicle['model']) ?></span>
        </div>

        <div class="vehicle-detail-price">
            <?= formatPrice($vehicle['price_per_day']) ?>
            <span>/ day</span>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="booking-form">
            <?= csrfField() ?>
            <input type="hidden" id="price_per_day" value="<?= $vehicle['price_per_day'] ?>">

            <h3>Select Rental Dates</h3>

            <div class="date-inputs">
                <div class="form-group">
                    <label class="form-label" for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control"
                           required value="<?= e($_POST['start_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control"
                           required value="<?= e($_POST['end_date'] ?? '') ?>">
                </div>
            </div>

            <div class="booking-summary">
                <h4>Booking Summary</h4>
                <div class="booking-summary-row">
                    <span>Vehicle:</span>
                    <span><?= e($vehicle['name']) ?></span>
                </div>
                <div class="booking-summary-row">
                    <span>Price per day:</span>
                    <span><?= formatPrice($vehicle['price_per_day']) ?></span>
                </div>
                <div class="booking-summary-row">
                    <span>Duration:</span>
                    <span id="total_days">-- day(s)</span>
                </div>
                <div class="booking-summary-row">
                    <span>Total Price:</span>
                    <span id="total_price">--</span>
                </div>
            </div>

            <div class="mt-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-lg">Confirm Booking</button>
                <a href="/Rental/vehicle.php?id=<?= $vehicle['id'] ?>" class="btn btn-outline btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
