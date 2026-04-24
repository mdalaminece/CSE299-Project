<?php
require_once __DIR__ . '/bootstrap.php';
require_login();

$user = current_user();
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $clientId = $user['role'] === 'client' ? (int) $user['id'] : (int) ($_POST['client_id'] ?? 0);
        $trainerId = (int) ($_POST['trainer_id'] ?? 0);
        $packageId = (int) ($_POST['package_id'] ?? 0);
        $sessionDate = $_POST['session_date'] ?? '';

        if ($clientId <= 0 || $sessionDate === '') {
            flash('error', 'Please complete the booking form.');
            redirect('bookings.php');
        }

        execute_query(
            'INSERT INTO bookings (client_id, trainer_id, package_id, session_date, status) VALUES (?, ?, ?, ?, ?)',
            [$clientId, $trainerId ?: null, $packageId ?: null, $sessionDate, 'booked']
        );
        flash('success', 'Booking created successfully.');
        redirect('bookings.php');
    }

    if ($action === 'status' && in_array($user['role'], ['admin', 'trainer'], true)) {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $status = $_POST['status'] ?? 'booked';
        if (!in_array($status, ['booked', 'completed', 'cancelled'], true)) {
            flash('error', 'Invalid booking status.');
            redirect('bookings.php');
        }

        execute_query('UPDATE bookings SET status = ? WHERE id = ?', [$status, $bookingId]);
        flash('success', 'Booking status updated.');
        redirect('bookings.php');
    }
}

$clients = fetch_all("SELECT id, name FROM users WHERE role = 'client' ORDER BY name ASC");
$trainers = fetch_all("SELECT id, name FROM users WHERE role = 'trainer' ORDER BY name ASC");
$packages = fetch_all('SELECT id, name FROM packages ORDER BY price ASC');

$bookingsSql = 'SELECT b.*, c.name AS client_name, t.name AS trainer_name, p.name AS package_name
                FROM bookings b
                LEFT JOIN users c ON c.id = b.client_id
                LEFT JOIN users t ON t.id = b.trainer_id
                LEFT JOIN packages p ON p.id = b.package_id';
$params = [];
if ($user['role'] === 'client') {
    $bookingsSql .= ' WHERE b.client_id = ?';
    $params[] = $user['id'];
} elseif ($user['role'] === 'trainer') {
    $bookingsSql .= ' WHERE b.trainer_id = ?';
    $params[] = $user['id'];
}
$bookingsSql .= ' ORDER BY b.session_date DESC';
$bookings = fetch_all($bookingsSql, $params);

$pageTitle = page_title('Bookings');
$activePage = 'bookings';
require_once __DIR__ . '/header.php';
?>
<main class="dashboard-page">
    <section class="page-heading">
        <div><span class="eyebrow">Bookings</span><h1>Session scheduling</h1><p>Manage trainer sessions, package-based bookings, and status updates from one screen.</p></div>
    </section>
    <section class="content-grid two-columns">
        <article class="table-card">
            <div class="card-heading"><h2><?= $user['role'] === 'client' ? 'Book a Session' : 'Create Booking' ?></h2></div>
            <form method="post" class="form-grid">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">
                <?php if ($user['role'] !== 'client'): ?>
                    <label><span>Client</span><select name="client_id" required><option value="">Select client</option><?php foreach ($clients as $client): ?><option value="<?= e((string) $client['id']) ?>"><?= e($client['name']) ?></option><?php endforeach; ?></select></label>
                <?php endif; ?>
                <label><span>Trainer</span><select name="trainer_id"><option value="">Choose trainer</option><?php foreach ($trainers as $trainer): ?><option value="<?= e((string) $trainer['id']) ?>"><?= e($trainer['name']) ?></option><?php endforeach; ?></select></label>
                <label><span>Package</span><select name="package_id"><option value="">Choose package</option><?php foreach ($packages as $package): ?><option value="<?= e((string) $package['id']) ?>"><?= e($package['name']) ?></option><?php endforeach; ?></select></label>
                <label><span>Session date & time</span><input type="datetime-local" name="session_date" required></label>
                <button class="button" type="submit">Save Booking</button>
            </form>
        </article>
        <article class="table-card">
            <div class="card-heading"><h2>Booking List</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Client</th><th>Trainer</th><th>Package</th><th>Date</th><th>Status</th><?php if (in_array($user['role'], ['admin', 'trainer'], true)): ?><th>Update</th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= e($booking['client_name'] ?? 'Unknown') ?></td>
                            <td><?= e($booking['trainer_name'] ?? 'Unassigned') ?></td>
                            <td><?= e($booking['package_name'] ?? 'Custom Session') ?></td>
                            <td><?= e(date('d M Y, h:i A', strtotime($booking['session_date']))) ?></td>
                            <td><span class="status status-<?= e($booking['status']) ?>"><?= e(ucfirst($booking['status'])) ?></span></td>
                            <?php if (in_array($user['role'], ['admin', 'trainer'], true)): ?>
                                <td>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">
                                        <select name="status">
                                            <option value="booked" <?= $booking['status'] === 'booked' ? 'selected' : '' ?>>Booked</option>
                                            <option value="completed" <?= $booking['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $booking['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" class="button tiny">Update</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
