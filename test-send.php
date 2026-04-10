<?php
require_once __DIR__ . '/otp.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        $res = $otpManager->requestNewOTP($email, '', 'test');
        $message = $res['message'] ?? 'OTP attempted. Check logs.';
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Test OTP Send</title>
<link rel="stylesheet" href="styles.css">
<style>.wrap{max-width:520px;margin:40px auto;padding:20px}</style>
</head>
<body>
<div class="wrap">
    <h1>Send Test OTP</h1>
    <?php if ($message): ?>
        <div class="alert"><?=htmlspecialchars($message)?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <button class="btn">Send OTP</button>
    </form>
    <p>Check <strong>otp_data/otp_log.txt</strong> and <strong>otp_data/email_failures.txt</strong> for results.</p>
</div>
</body>
</html>
