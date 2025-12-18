<?php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Vehicle Rental') ?> - Carental</title>
    <link rel="stylesheet" href="/Rental/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="/Rental/" class="logo">
                <img src="/Rental/logo/logo.png" alt="Carental" class="logo-img">
                <span>Carental</span>
            </a>

            <ul class="nav-links">
                <li><a href="/Rental/">Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="/Rental/my-bookings.php">My Bookings</a></li>
                    <li class="user-menu">
                        <span class="user-name">Hi, <?= e($_SESSION['user_name']) ?></span>
                        <a href="/Rental/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
                    </li>
                <?php else: ?>
                    <li><a href="/Rental/auth/login.php">Login</a></li>
                    <li><a href="/Rental/auth/register.php" class="btn btn-primary btn-sm">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <?= flashMessage() ?>
