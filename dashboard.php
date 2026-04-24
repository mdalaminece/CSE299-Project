<?php
require_once __DIR__ . '/bootstrap.php';
require_login();

$user = current_user();
$pageTitle = page_title('Dashboard');
$activePage = 'dashboard';

$stats = [
    'total_members' => count_value("SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'total_trainers' => count_value("SELECT COUNT(*) FROM users WHERE role = 'trainer'"),
    'total_bookings' => count_value('SELECT COUNT(*) FROM bookings'),
    'total_packages' => count_value('SELECT COUNT(*) FROM packages'),
    'total_revenue' => sum_value('SELECT COALESCE(SUM(amount), 0) FROM payments'),
];

if ($user['role'] === 'client') {
    $stats['my_bookings'] = count_value('SELECT COUNT(*) FROM bookings WHERE client_id = ?', [$user['id']]);
    $stats['my_payments'] = sum_value('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE user_id = ?', [$user['id']]);
    $stats['my_attendance'] = count_value('SELECT COUNT(*) FROM attendance WHERE user_id = ?', [$user['id']]);
}

if ($user['role'] === 'trainer') {
    $stats['assigned_sessions'] = count_value('SELECT COUNT(*) FROM bookings WHERE trainer_id = ?', [$user['id']]);
    $stats['completed_sessions'] = count_value("SELECT COUNT(*) FROM bookings WHERE trainer_id = ? AND status = 'completed'", [$user['id']]);
}

$recentBookings = fetch_all(
    'SELECT b.id, b.session_date, b.status, c.name AS client_name, t.name AS trainer_name, p.name AS package_name
     FROM bookings b
     LEFT JOIN users c ON c.id = b.client_id
     LEFT JOIN users t ON t.id = b.trainer_id
     LEFT JOIN packages p ON p.id = b.package_id
     ORDER BY b.session_date DESC
     LIMIT 6'
);
$recentPayments = fetch_all(
    'SELECT p.amount, p.method, p.paid_at, u.name
     FROM payments p
     INNER JOIN users u ON u.id = p.user_id
     ORDER BY p.paid_at DESC
     LIMIT 6'
);

require_once __DIR__ . '/header.php';
?>
<main class="dashboard-page">
    <section class="page-heading">
        <div>
            <span class="eyebrow"><?= e(ucfirst($user['role'])) ?> Dashboard</span>
            <h1>Welcome, <?= e($user['name']) ?></h1>
            <p>Here’s a live view of activity across your Alamin Fitness system.</p>
        </div>
    </section>

    <section class="dashboard-stats">
        <?php if ($user['role'] === 'admin'): ?>
            <article class="stat-card"><span>Members</span><strong><?= e((string) $stats['total_members']) ?></strong></article>
            <article class="stat-card"><span>Trainers</span><strong><?= e((string) $stats['total_trainers']) ?></strong></article>
            <article class="stat-card"><span>Bookings</span><strong><?= e((string) $stats['total_bookings']) ?></strong></article>
            <article class="stat-card"><span>Revenue</span><strong><?= e(format_money($stats['total_revenue'])) ?></strong></article>
        <?php elseif ($user['role'] === 'trainer'): ?>
            <article class="stat-card"><span>Assigned Sessions</span><strong><?= e((string) $stats['assigned_sessions']) ?></strong></article>
            <article class="stat-card"><span>Completed</span><strong><?= e((string) $stats['completed_sessions']) ?></strong></article>
            <article class="stat-card"><span>Packages</span><strong><?= e((string) $stats['total_packages']) ?></strong></article>
            <article class="stat-card"><span>Revenue</span><strong><?= e(format_money($stats['total_revenue'])) ?></strong></article>
        <?php else: ?>
            <article class="stat-card"><span>My Bookings</span><strong><?= e((string) $stats['my_bookings']) ?></strong></article>
            <article class="stat-card"><span>My Attendance</span><strong><?= e((string) $stats['my_attendance']) ?></strong></article>
            <article class="stat-card"><span>My Payments</span><strong><?= e(format_money($stats['my_payments'])) ?></strong></article>
            <article class="stat-card"><span>Packages</span><strong><?= e((string) $stats['total_packages']) ?></strong></article>
        <?php endif; ?>
    </section>

    <section class="content-grid">
        <article class="table-card">
            <div class="card-heading"><h2>Recent Bookings</h2><a href="<?= e(app_url('bookings.php')) ?>">View all</a></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Client</th><th>Trainer</th><th>Package</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentBookings as $booking): ?>
                        <tr>
                            <td><?= e($booking['client_name'] ?? 'Unknown') ?></td>
                            <td><?= e($booking['trainer_name'] ?? 'Unassigned') ?></td>
                            <td><?= e($booking['package_name'] ?? 'Custom Session') ?></td>
                            <td><?= e(date('d M Y, h:i A', strtotime($booking['session_date']))) ?></td>
                            <td><span class="status status-<?= e($booking['status']) ?>"><?= e(ucfirst($booking['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
        <article class="table-card">
            <div class="card-heading"><h2>Recent Payments</h2><a href="<?= e(app_url('payments.php')) ?>">Open payments</a></div>
            <div class="payment-feed">
                <?php foreach ($recentPayments as $payment): ?>
                    <div class="feed-row">
                        <div><strong><?= e($payment['name']) ?></strong><p><?= e($payment['method'] ?: 'Manual entry') ?></p></div>
                        <div class="feed-amount"><strong><?= e(format_money((float) $payment['amount'])) ?></strong><span><?= e(date('d M Y', strtotime($payment['paid_at']))) ?></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
