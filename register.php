<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/otp_manager.php';
require_once __DIR__ . '/otp_mailer.php';

// Start output buffering to prevent header issues
ob_start();

$reg_error = '';
$reg_success = '';
$step = 'register'; // 'register', 'verify_otp', or 'success'
$temp_email = '';
$otp_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'register';

    if ($action === 'register') {
        // Collect registration details
        $fullName = trim($_POST['fullName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirmPassword'] ?? '';
        $terms = isset($_POST['terms']) ? true : false;

        // Basic server-side validation
        if (!$fullName || !$email || !$password) {
            $reg_error = 'Please fill all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $reg_error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 8) {
            $reg_error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $reg_error = 'Passwords do not match.';
        } elseif (!$terms) {
            $reg_error = 'You must agree to the Terms and Conditions.';
        } else {
            // Check if email is already registered
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
                $users = [];
                if (file_exists($usersFile)) {
                    $raw = file_get_contents($usersFile);
                    $users = json_decode($raw, true);
                    if (!is_array($users)) $users = [];
                }
                if (isset($users[$email])) {
                    $emailExists = true;
                }
            }

            if ($emailExists) {
                $reg_error = 'Email already registered.';
            } else {
                // Create user with unverified email
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $userCreated = false;
                
                if ($pdo) {
                    try {
                        $ins = $pdo->prepare('INSERT INTO ch_users (email, full_name, phone, password, created_at, email_verified, balance) VALUES (:email, :full_name, :phone, :password, :created_at, :email_verified, :balance)');
                        $ins->execute([
                            ':email' => $email,
                            ':full_name' => $fullName,
                            ':phone' => $phone,
                            ':password' => $hashed,
                            ':created_at' => date('Y-m-d H:i:s'),
                            ':email_verified' => 0,
                            ':balance' => 0.00,
                        ]);
                        $userCreated = true;
                    } catch (Exception $e) {
                        $pdo = null;
                    }
                }

                if (!$userCreated && !$pdo) {
                    $usersFile = __DIR__ . '/users.json';
                    $users = [];
                    if (file_exists($usersFile)) {
                        $raw = file_get_contents($usersFile);
                        $users = json_decode($raw, true);
                        if (!is_array($users)) $users = [];
                    }

                    $users[$email] = [
                        'fullName' => $fullName,
                        'phone' => $phone,
                        'password' => $hashed,
                        'created_at' => date('Y-m-d H:i:s'),
                        'email_verified' => false,
                        'balance' => 0
                    ];

                    $written = @file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                    if ($written === false) {
                        $reg_error = 'Failed to save account. Check directory permissions on users.json.';
                    } else {
                        $userCreated = true;
                    }
                }

                if ($userCreated) {
                    // Generate and send OTP
                    $otpManager = new OTPManager();
                    $otp_code = $otpManager->storeOTP($email, 'registration');
                    
                    if ($otp_code) {
                        $mailer = new OTPMailer();
                        $mailSent = $mailer->sendOTP($email, $otp_code, 'registration');
                        
                        if ($mailSent) {
                            $step = 'verify_otp';
                            $temp_email = $email;
                            $otp_message = "A 6-digit verification code has been sent to <strong>$email</strong>. It will expire in 10 minutes.";
                            $_SESSION['temp_email'] = $email;
                        } else {
                            $reg_error = 'Account created, but failed to send verification email. Please try again.';
                        }
                    } else {
                        $reg_error = 'Account created, but failed to generate OTP. Please try again.';
                    }
                }
            }
        }
    } elseif ($action === 'resend_otp') {
        // Handle OTP resend
        $email = trim($_POST['email'] ?? '');
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $reg_error = 'Invalid email address.';
            $step = 'verify_otp';
            $temp_email = $email;
        } else {
            // Generate and send new OTP
            $otpManager = new OTPManager();
            $otp_code = $otpManager->storeOTP($email, 'registration');
            
            if ($otp_code) {
                $mailer = new OTPMailer();
                $mailSent = $mailer->sendOTP($email, $otp_code, 'registration');
                
                if ($mailSent) {
                    $step = 'verify_otp';
                    $temp_email = $email;
                    $otp_message = "✓ New verification code sent to <strong>$email</strong>. Valid for 10 minutes.";
                    $_SESSION['temp_email'] = $email;
                } else {
                    $reg_error = 'Failed to send verification email. Please try again.';
                    $step = 'verify_otp';
                    $temp_email = $email;
                }
            } else {
                $reg_error = 'Failed to generate OTP. Please try again.';
                $step = 'verify_otp';
                $temp_email = $email;
            }
        }
    } elseif ($action === 'verify_otp') {
        // Handle OTP verification
        $email = trim($_POST['email'] ?? '');
        $otp_input = trim($_POST['otp'] ?? '');

        if (!$email || !$otp_input) {
            $reg_error = 'Please enter the OTP code.';
            $step = 'verify_otp';
            $temp_email = $email;
        } else {
            $otpManager = new OTPManager();
            if ($otpManager->verifyOTP($email, $otp_input, 'registration')) {
                // OTP is valid - mark email as verified
                $pdo = getDB();
                $verified = false;
                
                if ($pdo) {
                    try {
                        $upd = $pdo->prepare('UPDATE ch_users SET email_verified = 1 WHERE email = :email');
                        $upd->execute([':email' => $email]);
                        $verified = true;
                    } catch (Exception $e) {
                        $pdo = null;
                    }
                }

                if (!$verified && !$pdo) {
                    $usersFile = __DIR__ . '/users.json';
                    if (file_exists($usersFile)) {
                        $users = json_decode(file_get_contents($usersFile), true) ?? [];
                        if (isset($users[$email])) {
                            $users[$email]['email_verified'] = true;
                            @file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                            $verified = true;
                        }
                    }
                }

                if ($verified) {
                    $step = 'success';
                    unset($_SESSION['temp_email']);
                } else {
                    $reg_error = 'Could not verify email. Please try again.';
                    $step = 'verify_otp';
                    $temp_email = $email;
                }
            } else {
                $reg_error = 'Invalid or expired OTP code. Please check:
                <br>• You entered the 6-digit code correctly
                <br>• The code matches what was sent to your email
                <br>• The code hasn\'t expired (valid for 10 minutes)
                <br><br>If the problem persists, use "Resend Code" to get a new code.';
                $step = 'verify_otp';
                $temp_email = $email;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CryptoHub</title>
    <link rel="stylesheet" href="styles.css">
    <?php if ($step === 'success'): ?>
        <meta http-equiv="refresh" content="3;url=index.php?registration_success=1">
    <?php endif; ?>
</head>
<body>
    <nav class="navbar navbar-auth">
        <div class="nav-container">
            <a href="index.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <li><a href="home.php" class="nav-link">Home</a></li>
                <li><a href="index.php" class="nav-link">Login</a></li>
                <li><a href="register.php" class="nav-link active">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="register-container">
        <div class="register-box">
            <h1>CryptoHub</h1>
            <h2><?php 
                if ($step === 'register') echo 'Create Account';
                elseif ($step === 'success') echo 'Success!';
                else echo 'Create Account';
            ?></h2>
            
            <?php if ($reg_error): ?>
                <div class="error-box" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                    <?=htmlspecialchars($reg_error)?>
                </div>
            <?php endif; ?>
            
            <?php if ($reg_success): ?>
                <div class="success-box" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                    <?=htmlspecialchars($reg_success)?>
                </div>
                <!-- Debug OTP display removed -->
            <?php endif; ?>
            
            <!-- Debug info removed -->
            
            <?php if ($step === 'register'): ?>
                <!-- Registration Form -->
                <form id="registerForm" method="POST" action="register.php">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="regFullName">Full Name:</label>
                        <input 
                            type="text" 
                            id="regFullName" 
                            name="fullName" 
                            placeholder="Enter your full name"
                            value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>"
                        >
                        <span class="error" id="regFullNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="regEmail">Email:</label>
                        <input 
                            type="text" 
                            id="regEmail" 
                            name="email" 
                            placeholder="Enter your email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                        <span class="error" id="regEmailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="regPhone">Phone Number:</label>
                        <input 
                            type="text" 
                            id="regPhone" 
                            name="phone" 
                            placeholder="Enter your phone number"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                        >
                        <span class="error" id="regPhoneError"></span>
                    </div>

                    <div class="form-group">
                        <label for="regPassword">Password:</label>
                        <input 
                            type="password" 
                            id="regPassword" 
                            name="password" 
                            placeholder="Create a strong password"
                        >
                        <span class="error" id="regPasswordError"></span>
                        <small>Password must be at least 8 characters</small>
                        <div id="regPasswordStrength"></div>
                    </div>

                    <div class="form-group">
                        <label for="regConfirmPassword">Confirm Password:</label>
                        <input 
                            type="password" 
                            id="regConfirmPassword" 
                            name="confirmPassword" 
                            placeholder="Confirm your password"
                        >
                        <span class="error" id="regConfirmPasswordError"></span>
                    </div>

                    <div class="form-group checkbox">
                        <label>
                            <input type="checkbox" name="terms"> 
                            I agree to the <a href="#">Terms and Conditions</a>
                        </label>
                        <span class="error" id="regTermsError"></span>
                    </div>

                    <button type="submit" class="btn">Create Account</button>
                </form>
            <?php elseif ($step === 'verify_otp'): ?>
                <!-- OTP Verification Form -->
                <div style="background:#e7f3ff; padding:15px; border-radius:5px; border:1px solid #b3d9ff; margin-bottom:20px; font-size:14px; color:#004085;">
                    <strong>📧 Verification Code Sent</strong><br>
                    <?php echo $otp_message; ?>
                    <br><small style="display:block; margin-top:8px;">⏱️ Code expires in <strong>10 minutes</strong> from when it was sent.</small>
                </div>
                
                <form method="POST" action="register.php">
                    <input type="hidden" name="action" value="verify_otp">
                    <input type="hidden" name="email" value="<?=htmlspecialchars($temp_email)?>">
                    
                    <div class="form-group">
                        <label for="otp">Enter Verification Code:</label>
                        <input 
                            type="text" 
                            id="otp" 
                            name="otp" 
                            placeholder="6-digit code"
                            maxlength="6"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            autocomplete="off"
                            required
                        >
                        <span class="error" id="otpError"></span>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">Verify Email</button>
                </form>
                
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Didn't receive the code?</p>
                    <form method="POST" action="register.php" style="display: inline;">
                        <input type="hidden" name="action" value="resend_otp">
                        <input type="hidden" name="email" value="<?=htmlspecialchars($temp_email)?>">
                        <button type="submit" class="btn" style="background: #6c757d; width: 100%;">🔄 Resend Code</button>
                    </form>
                    <br><br>
                    <a href="register.php" style="color: #667eea; text-decoration: none; font-size: 14px;">← Back to Registration</a>
                </div>
            <?php elseif ($step === 'success'): ?>
                <!-- Success Page -->
                <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 24px; font-weight: bold;">✓ Account Created Successfully!</p>
                    <p style="margin: 15px 0 0 0; font-size: 16px;">You can now log in with your credentials.</p>
                </div>
                
                <div style="text-align: center;">
                    <a href="index.php?registration_success=1" class="btn" style="display: inline-block; padding: 12px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
                        Go to Login Page
                    </a>
                </div>
            <?php endif; ?>

            <p class="login-link">
                Already have an account? <a href="index.php">Login here</a>
            </p>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About CryptoHub</h3>
                    <p>CryptoHub is your trusted platform for buying, selling, and managing cryptocurrency with bank-grade security and lightning-fast transactions.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="home.php">Home</a></li>
                        <li><a href="index.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                        <li><a href="contact-us.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Support</h3>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Security</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <div class="footer-contact">
                        <span>📧</span>
                        <a href="mailto:support@cryptohub.com">support@cryptohub.com</a>
                    </div>
                    <div class="footer-contact">
                        <span>💬</span>
                        <span>24/7 Customer Support</span>
                    </div>
                    <div class="footer-contact">
                        <span>⏰</span>
                        <span>Response: 24 hours</span>
                    </div>
                    <div class="footer-socials">
                        <a href="#" class="social-icon" title="Facebook">f</a>
                        <a href="#" class="social-icon" title="Twitter">𝕏</a>
                        <a href="#" class="social-icon" title="LinkedIn">in</a>
                        <a href="#" class="social-icon" title="Instagram">📷</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024-<?= date('Y') ?> <strong>CryptoHub</strong>. All rights reserved.</p>
                <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a> | <a href="#">Cookie Policy</a></p>
                <p style="color: #999; margin-top: 15px; font-size: 12px;"></p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
