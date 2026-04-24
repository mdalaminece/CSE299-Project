<?php
require_once __DIR__ . '/bootstrap.php';

if (is_post()) {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        flash('error', 'Please fill in all required fields.');
        redirect('register.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        redirect('register.php');
    }
    if ($password !== $confirmPassword) {
        flash('error', 'Passwords do not match.');
        redirect('register.php');
    }
    if (fetch_one('SELECT id FROM users WHERE email = ?', [$email])) {
        flash('error', 'That email address is already registered.');
        redirect('register.php');
    }

    execute_query(
        'INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)',
        [$name, $email, password_hash($password, PASSWORD_DEFAULT), 'client', $phone !== '' ? $phone : null]
    );
    $user = fetch_one('SELECT * FROM users WHERE email = ?', [$email]);
    if ($user) {
        login_user($user);
    }
    flash('success', 'Your membership account has been created.');
    redirect('dashboard.php');
}

$pageTitle = page_title('Register');
require_once __DIR__ . '/header.php';
?>
<main class="auth-page">
    <section class="auth-card">
        <div>
            <span class="eyebrow">Become a Member</span>
            <h1>Create your Alamin account</h1>
            <p>New registrations are created as client accounts and can start booking right away.</p>
        </div>
        <form method="post" class="form-grid">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <label><span>Full name</span><input type="text" name="name" required></label>
            <label><span>Email</span><input type="email" name="email" required></label>
            <label><span>Phone</span><input type="text" name="phone"></label>
            <label><span>Password</span><input type="password" name="password" required></label>
            <label><span>Confirm password</span><input type="password" name="confirm_password" required></label>
            <button class="button" type="submit">Create Account</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
