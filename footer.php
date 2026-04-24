        <footer class="site-footer">
            <div class="footer-brand">
                <strong>Alamin Fitness</strong>
                <p>Professional gym operations, membership control, and training management in one streamlined platform.</p>
            </div>
            <div class="footer-links">
            <div>
                <span class="footer-title">Platform</span>
                <a href="<?= e(app_url($user ? 'dashboard.php' : 'index.php')) ?>"><?= $user ? 'Dashboard' : 'Home' ?></a>
                <a href="<?= e(app_url('packages.php')) ?>">Packages</a>
                <a href="<?= e(app_url('bookings.php')) ?>">Bookings</a>
            </div>
            <div>
                <span class="footer-title">Operations</span>
                <a href="<?= e(app_url('attendance.php')) ?>">Attendance</a>
                <a href="<?= e(app_url('payments.php')) ?>">Payments</a>
                <a href="<?= e(app_url($user && $user['role'] === 'admin' ? 'members.php' : ($user ? 'dashboard.php' : 'login.php'))) ?>"><?= $user && $user['role'] === 'admin' ? 'Members' : ($user ? 'Account' : 'Login') ?></a>
            </div>
            </div>
            <div class="footer-meta">
                <span class="footer-title">System</span>
                <span>Built with PHP, MySQL, HTML, CSS and JavaScript.</span>
                <span>Designed for fast daily gym management.</span>
            </div>
        </footer>
    </div>
    <script src="<?= e(app_url('app.js')) ?>"></script>
    <script src="<?= e(app_url('chat.js')) ?>"></script>
</body>
</html>
