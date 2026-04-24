<?php
require_once __DIR__ . '/bootstrap.php';
require_login();

$user = current_user();
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $targetUserId = $user['role'] === 'admin' ? (int) ($_POST['user_id'] ?? $user['id']) : (int) $user['id'];

    if ($action === 'check_in') {
        execute_query('INSERT INTO attendance (user_id, check_in) VALUES (?, NOW())', [$targetUserId]);
        flash('success', 'Check-in recorded.');
        redirect('attendance.php');
    }

    if ($action === 'check_out') {
        execute_query('UPDATE attendance SET check_out = NOW() WHERE user_id = ? AND check_out IS NULL ORDER BY id DESC LIMIT 1', [$targetUserId]);
        flash('success', 'Check-out recorded.');
        redirect('attendance.php');
    }
}

$members = fetch_all("SELECT id, name FROM users WHERE role = 'client' ORDER BY name ASC");
$attendanceSql = 'SELECT a.*, u.name, u.role FROM attendance a INNER JOIN users u ON u.id = a.user_id';
$params = [];
if ($user['role'] === 'client') {
    $attendanceSql .= ' WHERE a.user_id = ?';
    $params[] = $user['id'];
}
$attendanceSql .= ' ORDER BY a.check_in DESC';
$records = fetch_all($attendanceSql, $params);

$pageTitle = page_title('Attendance');
$activePage = 'attendance';
require_once __DIR__ . '/header.php';
?>
<main class="dashboard-page">
    <section class="page-heading">
        <div><span class="eyebrow">Attendance</span><h1>Check-in and check-out</h1><p>Log gym visits and keep a clean history of member attendance.</p></div>
    </section>
    <section class="content-grid two-columns">
        <article class="table-card">
            <div class="card-heading"><h2>Attendance Actions</h2></div>
            <form method="post" class="form-grid">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <?php if ($user['role'] === 'admin'): ?>
                    <label><span>Member</span><select name="user_id"><?php foreach ($members as $member): ?><option value="<?= e((string) $member['id']) ?>"><?= e($member['name']) ?></option><?php endforeach; ?></select></label>
                <?php endif; ?>
                <div class="action-row">
                    <button class="button" type="submit" name="action" value="check_in">Check In</button>
                    <button class="button secondary" type="submit" name="action" value="check_out">Check Out</button>
                </div>
            </form>
        </article>
        <article class="table-card">
            <div class="card-heading"><h2>Attendance History</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Role</th><th>Check In</th><th>Check Out</th></tr></thead>
                    <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= e($record['name']) ?></td>
                            <td><?= e(ucfirst($record['role'])) ?></td>
                            <td><?= e(date('d M Y, h:i A', strtotime($record['check_in']))) ?></td>
                            <td><?= e($record['check_out'] ? date('d M Y, h:i A', strtotime($record['check_out'])) : 'Active') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
