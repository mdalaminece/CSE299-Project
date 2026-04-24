<?php
require_once __DIR__ . '/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

if (is_post()) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = fetch_one('SELECT * FROM users WHERE email = ?', [$email]);

    $isValid = false;
    if ($user) {
        $stored = (string) $user['password'];
        $isValid = password_verify($password, $stored) || hash_equals($stored, $password);
    }

    if (!$isValid) {
        flash('error', 'Invalid email or password.');
        redirect('login.php');
    }

    $otp = random_int(100000, 999999);
    $_SESSION['pending_otp'] = $otp;
    $_SESSION['pending_user'] = $user;

    if (send_otp_email($user['email'], $otp)) {
        flash('success', 'An OTP has been sent to your email. Please verify to continue.');
        redirect('verify_otp.php');
    } else {
        flash('error', 'Failed to send OTP. Please try again.');
        redirect('login.php');
    }
}

$pageTitle = page_title('Login');
require_once __DIR__ . '/header.php';
?>
<main class="auth-page">
    <section class="auth-card">
        <div>
            <span class="eyebrow">Member Access</span>
            <h1>Sign in to Alamin Fitness</h1>
            <p>Use your admin, trainer, or client account to access the dashboard.</p>
        </div>
        <form method="post" class="form-grid">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <label><span>Email</span><input type="email" name="email" placeholder="name@example.com" required></label>
            <label><span>Password</span><input type="password" name="password" placeholder="Enter password" required></label>
            <button class="button" type="submit">Log In</button>
        </form>
        <p class="helper-text">New here? <a href="<?= e(app_url('register.php')) ?>">Create a client account</a></p>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
