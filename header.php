<?php
require_once __DIR__ . '/bootstrap.php';

$user = current_user();
$pageTitle = $pageTitle ?? 'Fitness';
$activePage = $activePage ?? '';
$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url('style.css')) ?>">
</head>

<body>
    <div class="site-shell">
        <header class="topbar">
            <a class="brand" href="<?= e(app_url('index.php')) ?>">
                <img class="brand-logo" src="<?= e(app_url('assets/images/Logo.png')) ?>" alt="Alamin Fitness logo">
                <span>
                    <strong>Fitness</strong>
                    <small>Performance Gym Management</small>
                </span>
            </a>
            <div class="topbar-actions">
                <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                </button>
            </div>
            <nav class="main-nav" data-nav>
                <a class="<?= $activePage === 'home' ? 'is-active' : '' ?>" href="<?= e(app_url('index.php')) ?>">Home</a>
                <?php if ($user): ?>
                    <a class="<?= $activePage === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(app_url('dashboard.php')) ?>">Dashboard</a>
                    <a class="<?= $activePage === 'packages' ? 'is-active' : '' ?>" href="<?= e(app_url('packages.php')) ?>">Packages</a>
                    <a class="<?= $activePage === 'bookings' ? 'is-active' : '' ?>" href="<?= e(app_url('bookings.php')) ?>">Bookings</a>
                    <a class="<?= $activePage === 'attendance' ? 'is-active' : '' ?>" href="<?= e(app_url('attendance.php')) ?>">Attendance</a>
                    <a class="<?= $activePage === 'payments' ? 'is-active' : '' ?>" href="<?= e(app_url('payments.php')) ?>">Payments</a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a class="<?= $activePage === 'members' ? 'is-active' : '' ?>" href="<?= e(app_url('members.php')) ?>">Members</a>
                    <?php endif; ?>
                    <a class="button ghost" href="<?= e(app_url('logout.php')) ?>">Logout</a>
                <?php else: ?>
                    <a href="<?= e(app_url('login.php')) ?>">Login</a>
                    <a class="button ghost" href="<?= e(app_url('register.php')) ?>">Join Now</a>
                <?php endif; ?>
            </nav>
        </header>
        <?php if ($flashes): ?>
            <section class="flash-stack">
                <?php foreach ($flashes as $flash): ?>
                    <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>