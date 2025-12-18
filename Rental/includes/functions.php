<?php
/**
 * Helper Functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize output to prevent XSS
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /Rental/auth/login.php');
        exit;
    }
}

/**
 * Require user to be admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /Rental/admin/login.php');
        exit;
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF input field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Redirect with flash message
 */
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Display flash message
 */
function flashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = e($_SESSION['flash_message']);
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return "<div class='alert alert-{$type}'>{$message}</div>";
    }
    return '';
}

/**
 * Format currency
 */
function formatPrice($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Calculate rental days
 */
function calculateDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $diff = $start->diff($end);
    return $diff->days + 1; // Include both start and end days
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Get status badge HTML
 */
function statusBadge($status) {
    $colors = [
        'available' => 'success',
        'rented' => 'warning',
        'maintenance' => 'danger',
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'completed' => 'info',
        'cancelled' => 'secondary'
    ];
    $color = $colors[$status] ?? 'secondary';
    return "<span class='badge badge-{$color}'>" . ucfirst(e($status)) . "</span>";
}

/**
 * Get current user data
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
