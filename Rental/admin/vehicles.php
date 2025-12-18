<?php
$pageTitle = 'Manage Vehicles';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';

        // Delete vehicle
        if ($action === 'delete') {
            $vehicleId = (int)$_POST['vehicle_id'];
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$vehicleId]);
            redirect('/Rental/admin/vehicles.php', 'Vehicle deleted successfully.', 'success');
        }

        // Add or Update vehicle
        if ($action === 'add' || $action === 'edit') {
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $brand = trim($_POST['brand'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $pricePerDay = (float)($_POST['price_per_day'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $status = $_POST['status'] ?? 'available';
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);

            // Validation
            if (empty($name)) $errors[] = 'Vehicle name is required.';
            if (empty($type)) $errors[] = 'Vehicle type is required.';
            if (empty($brand)) $errors[] = 'Brand is required.';
            if ($pricePerDay <= 0) $errors[] = 'Price must be greater than 0.';

            // Handle image upload
            $imagePath = $_POST['existing_image'] ?? '';

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $fileType = $_FILES['image']['type'];

                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = 'Invalid image type. Allowed: JPG, PNG, WebP, GIF';
                } else {
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $newFileName = uniqid('vehicle_') . '.' . $extension;
                    $uploadPath = __DIR__ . '/../Images/' . $newFileName;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                        $imagePath = 'Images/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to upload image.';
                    }
                }
            }

            if (empty($errors)) {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO vehicles (name, type, brand, model, price_per_day, image, description, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $type, $brand, $model, $pricePerDay, $imagePath, $description, $status]);
                    redirect('/Rental/admin/vehicles.php', 'Vehicle added successfully!', 'success');
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE vehicles
                        SET name = ?, type = ?, brand = ?, model = ?, price_per_day = ?, image = ?, description = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $type, $brand, $model, $pricePerDay, $imagePath, $description, $status, $vehicleId]);
                    redirect('/Rental/admin/vehicles.php', 'Vehicle updated successfully!', 'success');
                }
            }
        }
    }
}

// Get all vehicles
$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY created_at DESC")->fetchAll();

// Get vehicle for editing
$editVehicle = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editVehicle = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-header">
    <h1>Manage Vehicles</h1>
    <a href="/Rental/auth/logout.php" class="btn btn-outline">Logout</a>
</div>

<nav class="admin-nav">
    <a href="/Rental/admin/">Dashboard</a>
    <a href="/Rental/admin/vehicles.php" class="active">Vehicles</a>
    <a href="/Rental/admin/bookings.php">Bookings</a>
</nav>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Add/Edit Vehicle Form -->
<div class="card mb-3">
    <div class="card-body">
        <h2><?= $editVehicle ? 'Edit Vehicle' : 'Add New Vehicle' ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editVehicle ? 'edit' : 'add' ?>">
            <?php if ($editVehicle): ?>
                <input type="hidden" name="vehicle_id" value="<?= $editVehicle['id'] ?>">
                <input type="hidden" name="existing_image" value="<?= e($editVehicle['image']) ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Vehicle Name</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= e($editVehicle['name'] ?? '') ?>" placeholder="e.g., Toyota Vios 2023">
                </div>

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <?php
                        $types = ['Sedan', 'SUV', 'Van', 'Motorcycle', 'Pickup', 'Hatchback'];
                        foreach ($types as $type):
                            $selected = ($editVehicle['type'] ?? '') === $type ? 'selected' : '';
                        ?>
                            <option value="<?= $type ?>" <?= $selected ?>><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" required
                           value="<?= e($editVehicle['brand'] ?? '') ?>" placeholder="e.g., Toyota">
                </div>

                <div class="form-group">
                    <label class="form-label">Model</label>
                    <input type="text" name="model" class="form-control"
                           value="<?= e($editVehicle['model'] ?? '') ?>" placeholder="e.g., Vios 1.5G">
                </div>

                <div class="form-group">
                    <label class="form-label">Price Per Day (PHP)</label>
                    <input type="number" name="price_per_day" class="form-control" required min="0" step="0.01"
                           value="<?= e($editVehicle['price_per_day'] ?? '') ?>" placeholder="e.g., 1500">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <?php
                        $statuses = ['available' => 'Available', 'rented' => 'Rented', 'maintenance' => 'Maintenance'];
                        foreach ($statuses as $value => $label):
                            $selected = ($editVehicle['status'] ?? 'available') === $value ? 'selected' : '';
                        ?>
                            <option value="<?= $value ?>" <?= $selected ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Image</label>
                <input type="file" name="image" id="vehicle_image" class="form-control" accept="image/*">
                <?php if ($editVehicle && $editVehicle['image']): ?>
                    <p class="text-muted mt-1">Current: <?= e($editVehicle['image']) ?></p>
                    <img id="image_preview" src="/Rental/<?= e($editVehicle['image']) ?>"
                         style="max-width: 200px; margin-top: 10px; border-radius: 8px;">
                <?php else: ?>
                    <img id="image_preview" style="max-width: 200px; margin-top: 10px; border-radius: 8px; display: none;">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Enter vehicle description..."><?= e($editVehicle['description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-primary">
                    <?= $editVehicle ? 'Update Vehicle' : 'Add Vehicle' ?>
                </button>
                <?php if ($editVehicle): ?>
                    <a href="/Rental/admin/vehicles.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Vehicles List -->
<h2 class="mb-2">All Vehicles (<?= count($vehicles) ?>)</h2>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Type</th>
                <th>Brand</th>
                <th>Price/Day</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehicles as $vehicle): ?>
                <tr>
                    <td>
                        <img src="/Rental/<?= e($vehicle['image']) ?>" alt="<?= e($vehicle['name']) ?>"
                             style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                    </td>
                    <td><strong><?= e($vehicle['name']) ?></strong></td>
                    <td><?= e($vehicle['type']) ?></td>
                    <td><?= e($vehicle['brand']) ?></td>
                    <td><?= formatPrice($vehicle['price_per_day']) ?></td>
                    <td><?= statusBadge($vehicle['status']) ?></td>
                    <td>
                        <a href="?edit=<?= $vehicle['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="POST" style="display: inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    data-confirm="Are you sure you want to delete this vehicle?">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
