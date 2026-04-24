<?php
require_once __DIR__ . '/bootstrap.php';
require_login();

$user = current_user();
if (is_post()) {
    verify_csrf();
    $targetUserId = $user['role'] === 'admin' ? (int) ($_POST['user_id'] ?? 0) : (int) $user['id'];
    $amount = (float) ($_POST['amount'] ?? 0);
    $method = trim($_POST['method'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($targetUserId <= 0 || $amount <= 0) {
        flash('error', 'Please enter a valid payment.');
        redirect('payments.php');
    }

    execute_query(
        'INSERT INTO payments (user_id, amount, method, note, paid_at) VALUES (?, ?, ?, ?, NOW())',
        [$targetUserId, $amount, $method !== '' ? $method : null, $note !== '' ? $note : null]
    );
    flash('success', 'Payment recorded successfully.');
    redirect('payments.php');
}

$members = fetch_all("SELECT id, name FROM users WHERE role = 'client' ORDER BY name ASC");
$paymentsSql = 'SELECT p.*, u.name FROM payments p INNER JOIN users u ON u.id = p.user_id';
$params = [];
if ($user['role'] === 'client') {
    $paymentsSql .= ' WHERE p.user_id = ?';
    $params[] = $user['id'];
}
$paymentsSql .= ' ORDER BY p.paid_at DESC';
$payments = fetch_all($paymentsSql, $params);

$pageTitle = page_title('Payments');
$activePage = 'payments';
require_once __DIR__ . '/header.php';
?>
<main class="dashboard-page">
    <section class="page-heading">
        <div><span class="eyebrow">Payments</span><h1>Payment records</h1><p>Track membership payments with method, notes, and historical visibility.</p></div>
    </section>
    <section class="content-grid two-columns">
        <article class="table-card">
            <div class="card-heading"><h2><?= $user['role'] === 'client' ? 'Add My Payment' : 'Record Payment' ?></h2></div>
            <form method="post" class="form-grid">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <?php if ($user['role'] === 'admin'): ?>
                    <label><span>Member</span><select name="user_id" required><option value="">Select member</option><?php foreach ($members as $member): ?><option value="<?= e((string) $member['id']) ?>"><?= e($member['name']) ?></option><?php endforeach; ?></select></label>
                <?php endif; ?>
                <label><span>Amount</span><input type="number" name="amount" min="1" step="0.01" required></label>
                <label><span>Method</span><input type="text" name="method" placeholder="Cash, Card, Bkash, Nagad"></label>
                <label><span>Note</span><textarea name="note" rows="4" placeholder="Payment details"></textarea></label>
                <button class="button" type="submit">Save Payment</button>
            </form>
        </article>
        <article class="table-card">
            <div class="card-heading"><h2>Payment History</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Member</th><th>Amount</th><th>Method</th><th>Note</th><th>Paid At</th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= e($payment['name']) ?></td>
                            <td><?= e(format_money((float) $payment['amount'])) ?></td>
                            <td><?= e($payment['method'] ?: 'Manual') ?></td>
                            <td><?= e($payment['note'] ?: '-') ?></td>
                            <td><?= e(date('d M Y, h:i A', strtotime($payment['paid_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
