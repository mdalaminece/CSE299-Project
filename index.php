<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = page_title('Home');
$activePage = 'home';
$stats = [
    'members' => count_value("SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'trainers' => count_value("SELECT COUNT(*) FROM users WHERE role = 'trainer'"),
    'packages' => count_value('SELECT COUNT(*) FROM packages'),
    'revenue' => sum_value('SELECT COALESCE(SUM(amount), 0) FROM payments'),
];
$featuredPackages = fetch_all('SELECT * FROM packages ORDER BY price ASC');
$featuredTrainers = fetch_all("SELECT name, email, phone FROM users WHERE role = 'trainer' ORDER BY id ASC");

require_once __DIR__ . '/header.php';
?>
<main>
    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow">High performance gym operations</span>
            <h1>Build a stronger gym brand with a bold Alamin Fitness experience.</h1>
            <p>Manage memberships, bookings, attendance, payments, and trainer schedules from one fast, modern PHP platform tailored to your gym database.</p>
            <div class="hero-actions">
                <a class="button" href="<?= e(app_url(is_logged_in() ? 'dashboard.php' : 'register.php')) ?>"><?= is_logged_in() ? 'Open Dashboard' : 'Start Membership' ?></a>
                <a class="button secondary" href="<?= e(app_url('login.php')) ?>">Member Login</a>
            </div>
            <div class="hero-badges">
                <span>Live bookings</span>
                <span>Payment history</span>
                <span>Attendance tracking</span>
            </div>
        </div>
        <div class="hero-panel">
            <div class="panel-card accent">
                <p>Recorded revenue</p>
                <strong><?= e(format_money($stats['revenue'])) ?></strong>
                <small>Based on the payments table</small>
            </div>
            <div class="stats-grid">
                <article><span>Clients</span><strong><?= e((string) $stats['members']) ?></strong></article>
                <article><span>Trainers</span><strong><?= e((string) $stats['trainers']) ?></strong></article>
                <article><span>Packages</span><strong><?= e((string) $stats['packages']) ?></strong></article>
                <article><span>Workflow</span><strong>24/7</strong></article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="section-heading">
            <span class="eyebrow">Why Alamin Fitness</span>
            <h2>Everything your gym needs in one system</h2>
        </div>
        <div class="feature-grid">
            <article class="feature-card"><h3>Membership Control</h3><p>Register new members, monitor profiles, and keep your user roles organized.</p></article>
            <article class="feature-card"><h3>Session Booking</h3><p>Let clients reserve trainer sessions while admins keep schedules clean and visible.</p></article>
            <article class="feature-card"><h3>Attendance Flow</h3><p>Track check-ins and check-outs with a simple operational dashboard for daily usage.</p></article>
            <article class="feature-card"><h3>Payment Records</h3><p>Capture payment methods, notes, and revenue snapshots right from the application.</p></article>
        </div>
    </section>

    <section class="section split-section">
        <div>
            <div class="section-heading">
                <span class="eyebrow">Membership Plans</span>
                <h2>Flexible packages from your database</h2>
            </div>
            <div class="pricing-grid">
                <?php foreach ($featuredPackages as $package): ?>
                    <article class="pricing-card">
                        <p class="plan-name"><?= e($package['name']) ?></p>
                        <strong><?= e(format_money((float) $package['price'])) ?></strong>
                        <span><?= e((string) $package['duration_days']) ?> days access</span>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <div class="section-heading">
                <span class="eyebrow">Coaching Team</span>
                <h2>Meet the trainers</h2>
            </div>
            <div class="trainer-list">
                <?php foreach ($featuredTrainers as $trainer): ?>
                    <article class="trainer-card">
                        <h3><?= e($trainer['name']) ?></h3>
                        <p><?= e($trainer['email']) ?></p>
                        <span><?= e($trainer['phone']) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section cta">
        <div>
            <span class="eyebrow">Ready to launch</span>
            <h2>Use your existing gym database with a complete website today.</h2>
        </div>
        <a class="button" href="<?= e(app_url(is_logged_in() ? 'dashboard.php' : 'register.php')) ?>"><?= is_logged_in() ? 'Go to control center' : 'Create account' ?></a>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
