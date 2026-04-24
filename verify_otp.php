<?php
require_once __DIR__ . '/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

if (!isset($_SESSION['pending_otp']) || !isset($_SESSION['pending_user'])) {
    redirect('login.php');
}

if (is_post()) {
    verify_csrf();
    $entered_otp = $_POST['otp'] ?? '';

    if ((string)$entered_otp === (string)$_SESSION['pending_otp']) {
        $user = $_SESSION['pending_user'];
        login_user($user);
        
        unset($_SESSION['pending_otp'], $_SESSION['pending_user']);
        
        flash('success', 'Welcome back, ' . $user['name'] . '. OTP verified successfully.');
        redirect('dashboard.php');
    } else {
        flash('error', 'Invalid OTP. Please try again.');
        redirect('verify_otp.php');
    }
}

$pageTitle = page_title('Verify OTP');
require_once __DIR__ . '/header.php';
?>
<main class="auth-page">
    <section class="auth-card">
        <div>
            <span class="eyebrow">Security Verification</span>
            <h1>Enter OTP</h1>
            <p>Please check your email for a 6-digit one-time password and enter it below.</p>
        </div>
        <form method="post" class="form-grid">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <label><span>OTP Code</span><input type="text" name="otp" placeholder="123456" required></label>
            <button class="button" type="submit">Verify & Login</button>
        </form>
        <p class="helper-text"><a href="<?= e(app_url('login.php')) ?>">Back to Login</a></p>
    </section>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
