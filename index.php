<?php
require_once __DIR__ . '/auth.php';

// Start output buffering to prevent header issues
ob_start();

$login_error = '';
$login_success = '';

// Check if coming from registration
$registration_success = !empty($_GET['registration_success']) ? true : false;
if ($registration_success) {
    $login_success = '✓ Account created successfully! Please log in with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Convert email to lowercase for case-insensitive matching
    $email = strtolower($email);

    // Quick admin login shortcut - Check FIRST before any validation
    if ($email === 'admin' && $password === 'admin32') {
        login_admin('cryptoadminpirouser@gmail.com', 'Administrator');

        // Redirect to admin panel
        ob_end_clean();
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $url = $scheme . '://' . $host . $base . '/admin-panel.php';
        header('Location: ' . $url);
        exit();
    } elseif (!$email || !$password) {
        $login_error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_error = 'Please enter a valid email address. (e.g., cryptoadminpirouser@gmail.com)';
    } else {
        // Check admin credentials first (from admin-users.json with hashed passwords)
        $admin = null;
        if (function_exists('verify_admin_credentials')) {
            $admin = verify_admin_credentials($email, $password);
        }

        if ($admin) {
            // Admin login successful
            login_admin($admin['email'], $admin['fullName'] ?? $admin['full_name'] ?? 'Administrator');

            // Redirect to admin panel
            ob_end_clean();
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $url = $scheme . '://' . $host . $base . '/admin-panel.php';
            header('Location: ' . $url);
            exit();
        }

        // User login: check if they're a regular user
        $user = null;
        if (function_exists('verify_user_credentials')) {
            $user = verify_user_credentials($email, $password);
        }

        if ($user) {
            $userEmail = $email;
            $userName = $user['fullName'] ?? $user['full_name'] ?? $email;

            // Set user session
            login_user($userEmail, $userName);

            // Update last_login in DB if possible, otherwise update users.json as fallback
            if (function_exists('getDB')) {
                try {
                    $pdo = getDB();
                    if ($pdo) {
                        $upd = $pdo->prepare('UPDATE ch_users SET last_login = NOW() WHERE email = :email');
                        $upd->execute([':email' => $userEmail]);
                    }
                } catch (Exception $e) {
                    // ignore update failures
                }
            } else {
                $usersFile = __DIR__ . '/users.json';
                if (file_exists($usersFile)) {
                    $raw = file_get_contents($usersFile);
                    $users = json_decode($raw, true);
                    if (!is_array($users)) $users = [];
                    if (isset($users[$userEmail])) {
                        $users[$userEmail]['last_login'] = date('Y-m-d H:i:s');
                        @file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    }
                }
            }

            // Redirect to home
            ob_end_clean();
            
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $url = $scheme . '://' . $host . $base . '/home.php';

            header('Location: ' . $url);
            exit();
        } else {
            $login_error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoHub - Digital Currency Platform</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-auth">
        <div class="nav-container">
            <a href="index.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <li><a href="home.php" class="nav-link">Home</a></li>
                <li><a href="index.php" class="nav-link active">Login</a></li>
                <li><a href="register.php" class="nav-link">Register</a></li>
            </ul>
        </div>
    </nav>
            </ul>
        </div> 
    </nav>

    <div class="login-container">
        <div class="login-box">
            <h1>CryptoHub</h1>
            <h2>Login</h2>
            
            <?php if ($login_error): ?>
                <div class="error-box" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                    <?=htmlspecialchars($login_error)?>
                </div>
            <?php endif; ?>
            
            <?php if ($login_success): ?>
                <div class="success-box" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                    <?=htmlspecialchars($login_success)?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form id="loginForm" method="POST" action="index.php">
                    
                    <div class="form-group">
                        <label for="loginEmail">Email:</label>
                        <input 
                            type="text" 
                            id="loginEmail" 
                            name="email" 
                            placeholder="Enter your email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                        <span class="error" id="loginEmailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="loginPassword">Password:</label>
                        <input 
                            type="password" 
                            id="loginPassword" 
                            name="password" 
                            placeholder="Enter your password"
                        >
                        <span class="error" id="loginPasswordError"></span>
                    </div>

                    <div class="remember-forgot">
                        <label>
                            <input type="checkbox" name="remember"> Remember me
                        </label>
                        <a href="forgot-password.php">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn">Login</button>
                </form>
            
            <?php if ($login_error): ?>
                <p class="error" style="text-align:center;margin-top:8px;"><?=htmlspecialchars($login_error) ?></p>
            <?php elseif (isset($_GET['registered'])): ?>
                <p class="success" style="text-align:center;margin-top:8px;">Registration successful — please log in.</p>
            <?php endif; ?>

            <p class="signup-link">
                Don't have an account? <a href="register.php">Sign up here</a>
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
