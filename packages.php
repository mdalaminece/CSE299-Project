<?php
require_once __DIR__ . '/bootstrap.php';
require_login();

$user = current_user();
if (is_post()) {
    require_role(['admin']);
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $duration = (int) ($_POST['duration_days'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);

    if ($name === '' || $duration <= 0 || $price <= 0) {
        flash('error', 'Please enter valid package details.');
        redirect('packages.php');
    }

    execute_query('INSERT INTO packages (name, duration_days, price) VALUES (?, ?, ?)', [$name, $duration, $price]);
    flash('success', 'Package created successfully.');
    redirect('packages.php');
}

$packages = fetch_all('SELECT * FROM packages ORDER BY price ASC');
$pageTitle = page_title('Packages');
$activePage = 'packages';

require_once __DIR__ . '/header.php';
?>
<main class="dashboard-page">
    <section class="page-heading">
        <div><span class="eyebrow">Packages</span><h1>Membership plans</h1><p>Review the plans stored in your database and add new ones as an admin.</p></div>
    </section>
    <section class="content-grid two-columns">
        <article class="table-card">
            <div class="card-heading"><h2>Available Packages</h2></div>
            <div class="pricing-grid compact">
                <?php foreach ($packages as $package): ?>
                    <div class="pricing-card">
                        <p class="plan-name"><?= e($package['name']) ?></p>
                        <strong><?= e(format_money((float) $package['price'])) ?></strong>
                        <span><?= e((string) $package['duration_days']) ?> days</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
        <?php if ($user['role'] === 'admin'): ?>
            <article class="table-card">
                <div class="card-heading"><h2>Add Package</h2></div>
                <form method="post" class="form-grid">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <label><span>Package name</span><input type="text" name="name" required></label>
                    <label><span>Duration in days</span><input type="number" name="duration_days" min="1" required></label>
                    <label><span>Price</span><input type="number" name="price" min="1" step="0.01" required></label>
                    <button class="button" type="submit">Save Package</button>
                </form>
            </article>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
