<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/otp_manager.php';
require_once __DIR__ . '/otp_mailer.php';

// Simplified forgot-password: send OTP for password reset.
if (session_status() === PHP_SESSION_NONE) session_start();

$step = 'email';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'error';
        } else {
            // Check if email exists
            $pdo = getDB();
            $emailExists = false;
            
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare('SELECT id FROM ch_users WHERE email = :email LIMIT 1');
                    $stmt->execute([':email' => $email]);
                    if ($stmt->fetch()) {
                        $emailExists = true;
                    }
                } catch (Exception $e) {
                    $pdo = null;
                }
            }
            
            if (!$emailExists && !$pdo) {
                $usersFile = __DIR__ . '/users.json';
                if (file_exists($usersFile)) {
                    $users = json_decode(file_get_contents($usersFile), true) ?? [];
                    if (isset($users[$email])) {
                        $emailExists = true;
                    }
                }
            }
            
            if (!$emailExists) {
                $message = 'No account found with this email address.';
                $message_type = 'error';
            } else {
                // Generate and send OTP
                $otpManager = new OTPManager();
                $otp_code = $otpManager->storeOTP($email, 'password_reset');
                
                if ($otp_code) {
                    $mailer = new OTPMailer();
                    $mailSent = $mailer->sendOTP($email, $otp_code, 'password_reset');
                    
                    if ($mailSent) {
                        $step = 'verify_otp';
                        $_SESSION['reset_email'] = $email;
                        $message = "A 6-digit verification code has been sent to <strong>$email</strong>. It will expire in 10 minutes.";
                        $message_type = 'info';
                    } else {
                        $message = 'Failed to send verification email. Please try again.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Failed to generate OTP. Please try again.';
                    $message_type = 'error';
                }
            }
        }
    } elseif ($action === 'resend_otp') {
        $email = trim($_POST['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'error';
            $step = 'verify_otp';
        } else {
            // Generate and send new OTP
            $otpManager = new OTPManager();
            $otp_code = $otpManager->storeOTP($email, 'password_reset');
            
            if ($otp_code) {
                $mailer = new OTPMailer();
                $mailSent = $mailer->sendOTP($email, $otp_code, 'password_reset');
                
                if ($mailSent) {
                    $step = 'verify_otp';
                    $_SESSION['reset_email'] = $email;
                    $message = "✓ New verification code sent to <strong>$email</strong>. Valid for 10 minutes.";
                    $message_type = 'success';
                } else {
                    $message = 'Failed to send verification email. Please try again.';
                    $message_type = 'error';
                    $step = 'verify_otp';
                }
            } else {
                $message = 'Failed to generate OTP. Please try again.';
                $message_type = 'error';
                $step = 'verify_otp';
            }
        }
    } elseif ($action === 'verify_otp') {
        $email = trim($_POST['email'] ?? '');
        $otp_input = trim($_POST['otp'] ?? '');

        if (!$email || !$otp_input) {
            $message = 'Please enter the OTP code.';
            $message_type = 'error';
            $step = 'verify_otp';
        } else {
            $otpManager = new OTPManager();
            if ($otpManager->verifyOTP($email, $otp_input, 'password_reset')) {
                $step = 'reset_password';
                $_SESSION['reset_email'] = $email;
                $message = 'Verification successful. Please enter your new password.';
                $message_type = 'success';
            } else {
                $message = 'Invalid or expired OTP. Please check:
                <br>• You entered the 6-digit code correctly
                <br>• The code matches what was sent to your email
                <br>• The code hasn\'t expired (valid for 10 minutes)
                <br><br>If the problem persists, use "Resend Code" to get a new code.';
                $message_type = 'error';
                $step = 'verify_otp';
            }
        }
    } elseif ($action === 'reset_password') {
        $email = trim($_POST['email'] ?? '');
        $newPassword = $_POST['newPassword'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        if (!$newPassword || !$confirmPassword) {
            $message = 'Please fill password fields.';
            $message_type = 'error';
            $step = 'reset_password';
        } elseif (strlen($newPassword) < 8) {
            $message = 'Password must be at least 8 characters.';
            $message_type = 'error';
            $step = 'reset_password';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Passwords do not match.';
            $message_type = 'error';
            $step = 'reset_password';
        } else {
            // Update password in DB
            $pdo = getDB();
            $applied = false;
            if ($pdo) {
                try {
                    $upd = $pdo->prepare('UPDATE ch_users SET password = :password WHERE email = :email');
                    $upd->execute([':password' => password_hash($newPassword, PASSWORD_DEFAULT), ':email' => $email]);
                    if ($upd->rowCount() > 0) $applied = true;
                } catch (Exception $e) {
                    $pdo = null;
                }
            }

            if (!$applied && !$pdo) {
                $usersFile = __DIR__ . '/users.json';
                if (file_exists($usersFile)) {
                    $users = json_decode(file_get_contents($usersFile), true) ?? [];
                    if (isset($users[$email])) {
                        $users[$email]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                        if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                            $applied = true;
                        }
                    }
                }
            }

            if ($applied) {
                $message = 'Password has been reset successfully. You may now log in.';
                $message_type = 'success';
                $step = 'done';
                unset($_SESSION['reset_email']);
            } else {
                $message = 'Failed to update password. Please try again later.';
                $message_type = 'error';
                $step = 'reset_password';
            }
        }
    }
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Forgot Password - CryptoHub</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            max-width:480px;
            margin: 40px auto;
            padding: 20px;
        }
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Forgot Password</h1>
    <?php if ($message): ?>
        <div class="alert <?=htmlspecialchars($message_type)?>"><?=html_entity_decode(htmlspecialchars($message, ENT_QUOTES))?></div>
    <?php endif; ?>

    <!-- Step 1: Enter email to send OTP -->
    <?php if ($step === 'email'): ?>
        <form method="POST">
            <input type="hidden" name="action" value="send_otp">
            <div class="form-group">
                <label>Email address</label>
                <input type="email" name="email" required>
            </div>
            <button type="submit" class="btn">Send Verification Code</button>
            <p><a href="index.php">Back to login</a></p>
        </form>

    <!-- Step 2: Verify OTP -->
    <?php elseif ($step === 'verify_otp'): ?>
        <div class="info-box">
            📧 <strong>Verification Code Sent</strong><br>
            A 6-digit code has been sent to <strong><?=htmlspecialchars($_SESSION['reset_email'] ?? 'your email')?></strong>.
            <br><small style="display:block; margin-top:8px;">⏱️ Code expires in <strong>10 minutes</strong> from when it was sent.</small>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="verify_otp">
            <input type="hidden" name="email" value="<?=htmlspecialchars($_SESSION['reset_email'] ?? '')?>">
            <div class="form-group">
                <label>Enter 6-digit code</label>
                <input type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Verify Code</button>
        </form>

        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Didn't receive the code?</p>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="resend_otp">
                <input type="hidden" name="email" value="<?=htmlspecialchars($_SESSION['reset_email'] ?? '')?>">
                <button type="submit" class="btn" style="background: #6c757d; width: 100%;">🔄 Resend Code</button>
            </form>
            <br><br>
            <a href="forgot-password.php" style="color: #667eea; text-decoration: none; font-size: 14px;">← Back to email</a>
        </div>

    <!-- Step 3: Reset password after OTP verification -->
    <?php elseif ($step === 'reset_password'): ?>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="email" value="<?=htmlspecialchars($_SESSION['reset_email'] ?? '')?>">
            <div class="form-group">
                <label for="resetNewPassword">New password</label>
                <input type="password" id="resetNewPassword" name="newPassword" required>
                <div id="resetNewPasswordStrength"></div>
            </div>
            <div class="form-group">
                <label for="resetConfirmPassword">Confirm new password</label>
                <input type="password" id="resetConfirmPassword" name="confirmPassword" required>
            </div>
            <button type="submit" class="btn">Reset Password</button>
        </form>

    <!-- Success message -->
    <?php elseif ($step === 'done'): ?>
        <p style="margin-top: 20px;"><a href="index.php" class="btn">Back to login</a></p>
    <?php endif; ?>

</div>
</body>
</html>
