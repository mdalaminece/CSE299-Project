<?php
require_once __DIR__ . '/bootstrap.php';
require_role(['admin']);

$members = fetch_all("SELECT id, name, email, role, phone, created_at FROM users ORDER BY FIELD(role, 'admin', 'trainer', 'client'), name ASC");
$pageTitle = page_title('Members');
$activePage = 'members';

require_once __DIR__ . '/header.php';
?>
<main class="dashboard-page">
    <section class="page-heading">
        <div><span class="eyebrow">Members</span><h1>User directory</h1><p>View all admins, trainers, and clients stored inside the gym database.</p></div>
    </section>
    <section class="table-card">
        <div class="card-heading"><h2>All Users</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= e($member['name']) ?></td>
                        <td><?= e($member['email']) ?></td>
                        <td><span class="status status-role"><?= e(ucfirst($member['role'])) ?></span></td>
                        <td><?= e($member['phone'] ?: '-') ?></td>
                        <td><?= e(date('d M Y', strtotime($member['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
