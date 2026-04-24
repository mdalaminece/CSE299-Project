<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Dhaka');

const DB_HOST = '127.0.0.1';
const DB_NAME = 'gym_management';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}

function app_url(string $path = ''): string
{
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = rtrim($scriptPath, '/.');
    $base = $base === '' ? '' : $base;

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_role(array $roles): void
{
    require_login();
    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        flash('error', 'You do not have permission to access that page.');
        redirect('dashboard.php');
    }
}

function redirect(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid form token.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function login_user(array $user): void
{
    $_SESSION['user'] = $user;
}

function logout_user(): void
{
    unset($_SESSION['user']);
}

function fetch_one(string $sql, array $params = []): ?array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $result = $statement->fetch();
    return $result === false ? null : $result;
}

function fetch_all(string $sql, array $params = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function execute_query(string $sql, array $params = []): bool
{
    $statement = db()->prepare($sql);
    return $statement->execute($params);
}

function count_value(string $sql, array $params = []): int
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return (int) $statement->fetchColumn();
}

function sum_value(string $sql, array $params = []): float
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return (float) ($statement->fetchColumn() ?: 0);
}

function format_money(float $amount): string
{
    return 'BDT ' . number_format($amount, 2);
}

function page_title(string $title): string
{
    return $title . ' | Alamin Fitness';
}

function send_otp_email(string $to, int $otp): bool
{
    require_once __DIR__ . '/otp/smtp/PHPMailerAutoload.php';
    $mail = new PHPMailer(); 
    $mail->IsSMTP(); 
    $mail->SMTPAuth = true; 
    $mail->SMTPSecure = 'tls'; 
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587; 
    $mail->IsHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Username = "hassanshuvo17.11.98@gmail.com"; 
    $mail->Password = "fwwn vrpn qxsl zxlt"; 
    $mail->SetFrom("hassanshuvo17.11.98@gmail.com", "Alamin Fitness");
    $mail->Subject = "Login Verification OTP";
    $mail->Body = "Your 6 Digit Login OTP Code is: <b>$otp</b>";
    $mail->AddAddress($to);
    $mail->SMTPOptions = array('ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => false
    ));
    return $mail->Send();
}
