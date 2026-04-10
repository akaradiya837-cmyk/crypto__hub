<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/otp_manager.php';
require_once __DIR__ . '/otp_mailer.php';

// Require login to access this page
require_user_or_admin_view();

// If admin is viewing user mode, set a JS flag
if (is_admin() && !isset($_SESSION['user_email']) && !empty($_GET['as_admin']) && $_GET['as_admin'] == '1') {
    $_SESSION['user_email'] = 'admin@crypto.com';
    $_SESSION['user_name'] = 'Admin UserMode';
}

$pdo = getDB();
$user_email = $_SESSION['user_email'] ?? '';
$user_data = [
    'fullName' => $_SESSION['user_name'] ?? 'User',
    'phone' => 'Not provided'
];

// Try to fetch user data from DB first, fallback to JSON
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT full_name, phone FROM ch_users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $user_email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $user_data = [
                'fullName' => $row['full_name'],
                'phone' => $row['phone'] ?? 'Not provided'
            ];
        }
    } catch (Exception $e) {
        // Fallback below
    }
}

// Fallback to JSON if DB unavailable
if (!$pdo || $user_data['fullName'] === ($_SESSION['user_name'] ?? 'User')) {
    $usersFile = __DIR__ . '/users.json';
    $users = [];
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true) ?? [];
        if (isset($users[$user_email])) {
            $user_data = [
                'fullName' => $users[$user_email]['fullName'] ?? ($_SESSION['user_name'] ?? 'User'),
                'phone' => $users[$user_email]['phone'] ?? 'Not provided'
            ];
        }
    }
}

$message = '';
$message_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['fullName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($fullName)) {
        $message = 'Name cannot be empty.';
        $message_type = 'error';
    } else {
        $updated = false;
        
        // Try DB first
        if ($pdo) {
            try {
                $upd = $pdo->prepare('UPDATE ch_users SET full_name = :full_name, phone = :phone WHERE email = :email');
                $upd->execute([
                    ':full_name' => $fullName,
                    ':phone' => $phone,
                    ':email' => $user_email
                ]);
                if ($upd->rowCount() > 0) {
                    $updated = true;
                }
            } catch (Exception $e) {
                // Continue to JSON fallback
            }
        }
        
        // Fallback to JSON if DB not available
        if (!$updated) {
            $usersFile = __DIR__ . '/users.json';
            $users = [];
            if (file_exists($usersFile)) {
                $users = json_decode(file_get_contents($usersFile), true) ?? [];
            }
            
            if (isset($users[$user_email])) {
                $users[$user_email]['fullName'] = $fullName;
                $users[$user_email]['phone'] = $phone;
                
                if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    $updated = true;
                }
            }
        }
        
        if ($updated) {
            // Update session
            $_SESSION['user_name'] = $fullName;
            $user_data['fullName'] = $fullName;
            $user_data['phone'] = $phone;
            $message = 'Profile updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update profile. Please try again.';
            $message_type = 'error';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (!$currentPassword || !$newPassword || !$confirmPassword) {
        $message = 'Please fill in all password fields.';
        $message_type = 'error';
    } elseif (strlen($newPassword) < 8) {
        $message = 'New password must be at least 8 characters.';
        $message_type = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match.';
        $message_type = 'error';
    } else {
        // Verify current password first
        $passwordVerified = false;
        $storedHash = null;
        
        // Try DB first
        if ($pdo) {
            try {
                $stmt = $pdo->prepare('SELECT password FROM ch_users WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $user_email]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $storedHash = $row['password'];
                }
            } catch (Exception $e) {
                // Continue to JSON fallback
            }
        }
        
        // Fallback to JSON if DB unavailable
        if (!$storedHash) {
            $usersFile = __DIR__ . '/users.json';
            $users = [];
            if (file_exists($usersFile)) {
                $users = json_decode(file_get_contents($usersFile), true) ?? [];
                if (isset($users[$user_email])) {
                    $storedHash = $users[$user_email]['password'] ?? null;
                }
            }
        }
        
        if (!$storedHash || !password_verify($currentPassword, $storedHash)) {
            $message = 'Current password is incorrect.';
            $message_type = 'error';
        } else {
            // Send OTP for password change verification
            $otpManager = new OTPManager();
            $otp_code = $otpManager->storeOTP($user_email, 'password_change');
            
            if ($otp_code) {
                $mailer = new OTPMailer();
                $mailSent = $mailer->sendOTP($user_email, $otp_code, 'password_change');
                
                if ($mailSent) {
                    // Store pending password change in session
                    $_SESSION['pending_password_change'] = [
                        'email' => $user_email,
                        'new_password' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'timestamp' => time()
                    ];
                    $_SESSION['show_otp_verification'] = true;
                    $message = 'A verification code has been sent to your email. Please enter it to confirm password change.';
                    $message_type = 'info';
                } else {
                    $message = 'Failed to send verification email. Please try again.';
                    $message_type = 'error';
                }
            } else {
                $message = 'Failed to generate verification code. Please try again.';
                $message_type = 'error';
            }
        }
    }
}

// Handle OTP verification for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend_password_otp') {
    $otpManager = new OTPManager();
    $otp_code = $otpManager->storeOTP($user_email, 'password_change');
    
    if ($otp_code) {
        $mailer = new OTPMailer();
        $mailSent = $mailer->sendOTP($user_email, $otp_code, 'password_change');
        
        if ($mailSent) {
            $_SESSION['show_otp_verification'] = true;
            $message = '✓ New verification code sent to your email. Valid for 10 minutes.';
            $message_type = 'success';
        } else {
            $message = 'Failed to send verification email. Please try again.';
            $message_type = 'error';
        }
    } else {
        $message = 'Failed to generate verification code. Please try again.';
        $message_type = 'error';
    }
}

// Handle OTP verification for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_password_otp') {
    $otp_input = trim($_POST['password_otp'] ?? '');
    
    if (!$otp_input) {
        $message = 'Please enter the verification code.';
        $message_type = 'error';
    } else {
        $otpManager = new OTPManager();
        if ($otpManager->verifyOTP($user_email, $otp_input, 'password_change') && isset($_SESSION['pending_password_change'])) {
            $pending = $_SESSION['pending_password_change'];
            
            // Update password in DB first
            $updated = false;
            if ($pdo) {
                try {
                    $upd = $pdo->prepare('UPDATE ch_users SET password = :password WHERE email = :email');
                    $upd->execute([
                        ':password' => $pending['new_password'],
                        ':email' => $user_email
                    ]);
                    if ($upd->rowCount() > 0) {
                        $updated = true;
                    }
                } catch (Exception $e) {
                    // Continue to JSON fallback
                }
            }
            
            // Fallback to JSON if DB not available
            if (!$updated) {
                $usersFile = __DIR__ . '/users.json';
                $users = [];
                if (file_exists($usersFile)) {
                    $users = json_decode(file_get_contents($usersFile), true) ?? [];
                }
                
                if (isset($users[$user_email])) {
                    $users[$user_email]['password'] = $pending['new_password'];
                    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                        $updated = true;
                    }
                }
            }
            
            if ($updated) {
                unset($_SESSION['pending_password_change']);
                unset($_SESSION['show_otp_verification']);
                $message = 'Password changed successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update password. Please try again.';
                $message_type = 'error';
            }
        } else {
            $message = 'Invalid or expired verification code. Please check:
            <br>• You entered the 6-digit code correctly
            <br>• The code matches what was sent to your email
            <br>• The code hasn\'t expired (valid for 10 minutes)
            <br><br>If the problem persists, use "Resend Code" to get a new code.';
            $message_type = 'error';
        }
    }
}

$is_admin = is_admin() && empty($_GET['as_admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_admin ? 'Admin Profile' : 'User Profile' ?> - CryptoHub</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .profile-header {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            flex-shrink: 0;
        }

        .profile-info h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .profile-role {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .profile-role.admin {
            background: #FFD700;
            color: #333;
        }

        .profile-role.user {
            background: #667eea;
            color: white;
        }

        .profile-email {
            color: #666;
            font-size: 16px;
            margin: 10px 0;
        }

        .profile-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .profile-section h2 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-field {
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            align-items: center;
        }

        .profile-field label {
            font-weight: 600;
            color: #333;
        }

        .profile-field-value {
            color: #666;
            padding: 10px 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .edit-toggle {
            margin-bottom: 20px;
        }

        .edit-toggle button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #667eea;
            color: white;
        }

        .btn-edit:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            background: #f0f0f0;
            color: #333;
            margin-left: 10px;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 10px;
            color: white;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }

        .edit-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-field {
                grid-template-columns: 1fr;
            }

            .profile-field label {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <?php $as_admin = !empty($_GET['as_admin']) ? '?as_admin=1' : ''; ?>
                <li><a href="home.php<?= $as_admin ?>" class="nav-link">Home</a></li>
                <li><a href="dashboard.php<?= $as_admin ?>" class="nav-link">Dashboard</a></li>
                <li><a href="add-money.php<?= $as_admin ?>" class="nav-link">Add Money</a></li>
                <li><a href="invest.php<?= $as_admin ?>" class="nav-link">Invest</a></li>
                <li><a href="contact-us.php<?= $as_admin ?>" class="nav-link">Contact Us</a></li>
                <li><a href="profile.php<?= $as_admin ?>" class="nav-link active">Profile</a></li>
                <?php if (is_admin() && empty($_GET['as_admin'])): ?>
                    <li><a href="admin-panel.php" class="nav-link" style="color: #FFD700;">🔐 Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="nav-link" id="logoutBtn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="profile-container">
        <?php if ($message): ?>
            <div class="alert <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php 
                    $first_letter = strtoupper(substr($user_data['fullName'] ?? 'U', 0, 1));
                    echo htmlspecialchars($first_letter);
                ?>
            </div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($user_data['fullName'] ?? 'User') ?></h1>
                <?php if ($is_admin): ?>
                    <span class="profile-role admin">🔐 Administrator</span>
                <?php else: ?>
                    <span class="profile-role user">👤 Regular User</span>
                <?php endif; ?>
                <div class="profile-email">
                    Email: <?= htmlspecialchars($user_email) ?>
                </div>
                <div class="profile-email">
                    Account Type: <?= $is_admin ? 'Administrator' : 'User' ?>
                </div>
                <div class="profile-email" style="font-size: 12px; color: #999;">
                    Member since: <?= date('F Y', strtotime('now - ' . rand(1, 12) . ' months')) ?>
                </div>
            </div>
        </div>

        <!-- Personal Information Section -->
        <div class="profile-section">
            <h2>Personal Information</h2>
            
            <?php if (!isset($_POST['edit_profile'])): ?>
                <div class="edit-toggle">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="edit_profile" value="1" class="btn-edit">✏️ Edit Profile</button>
                    </form>
                </div>

                <div class="profile-field">
                    <label>Full Name</label>
                    <div class="profile-field-value"><?= htmlspecialchars($user_data['fullName'] ?? 'Not provided') ?></div>
                </div>
                <div class="profile-field">
                    <label>Email Address</label>
                    <div class="profile-field-value"><?= htmlspecialchars($user_email) ?></div>
                </div>
                <div class="profile-field">
                    <label>Phone Number</label>
                    <div class="profile-field-value"><?= htmlspecialchars($user_data['phone'] ?? 'Not provided') ?></div>
                </div>

            <?php else: ?>
                <form method="POST" class="edit-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" value="<?= htmlspecialchars($user_data['fullName'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?= htmlspecialchars($user_email) ?>" readonly style="background: #f0f0f0; cursor: not-allowed;">
                        <small style="color: #999;">Email cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>">
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="reset" class="btn btn-cancel" onclick="location.reload();">Cancel</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($is_admin): ?>
            <!-- Admin Statistics Section -->
            <div class="profile-section">
                <h2>Admin Dashboard Overview</h2>
                
                <div class="admin-stats">
                    <div class="stat-card">
                        <h3>0</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-card">
                        <h3>0</h3>
                        <p>Active Transactions</p>
                    </div>
                    <div class="stat-card">
                        <h3>0</h3>
                        <p>Total Investments</p>
                    </div>
                    <div class="stat-card">
                        <h3>$0.00</h3>
                        <p>Platform Revenue</p>
                    </div>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: #f0f7ff; border-radius: 5px; border-left: 4px solid #667eea;">
                    <p style="color: #333; margin: 0;">
                        <strong>Admin Access:</strong> Use the Admin Panel to manage users, view reports, handle transactions, and configure system settings.
                    </p>
                </div>
            </div>

            <!-- Admin Quick Actions -->
            <div class="profile-section">
                <h2>Quick Admin Actions</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <a href="admin-panel.php" style="padding: 15px; background: #667eea; color: white; text-align: center; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#764ba2'" onmouseout="this.style.background='#667eea'">
                        📊 Admin Panel
                    </a>
                    <a href="admin-users.php" style="padding: 15px; background: #667eea; color: white; text-align: center; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#764ba2'" onmouseout="this.style.background='#667eea'">
                        👥 Manage Users
                    </a>
                    <a href="admin-transactions.php" style="padding: 15px; background: #667eea; color: white; text-align: center; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#764ba2'" onmouseout="this.style.background='#667eea'">
                        💰 Transactions
                    </a>
                    <a href="admin-reports.php" style="padding: 15px; background: #667eea; color: white; text-align: center; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#764ba2'" onmouseout="this.style.background='#667eea'">
                        📈 Reports
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- User Statistics Section -->
            <div class="profile-section">
                <h2>Your Account Statistics</h2>
                
                <div class="admin-stats">
                    <div class="stat-card">
                        <h3>$0.00</h3>
                        <p>Current Balance</p>
                    </div>
                    <div class="stat-card">
                        <h3>$0.00</h3>
                        <p>Total Invested</p>
                    </div>
                    <div class="stat-card">
                        <h3>+$0.00</h3>
                        <p>Profit/Loss</p>
                    </div>
                    <div class="stat-card">
                        <h3>0%</h3>
                        <p>Return on Investment</p>
                    </div>
                </div>
            </div>

            <!-- Account Security -->
            <div class="profile-section">
                <h2>Account Security</h2>
                
                <?php if (!isset($_POST['change_password'])): ?>
                    <div class="profile-field">
                        <label>Password</label>
                        <div class="profile-field-value" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>••••••••••••••</span>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="change_password" value="1" class="btn-edit" style="padding: 6px 12px; font-size: 12px;">Change Password</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (!empty($_SESSION['show_otp_verification'])): ?>
                        <!-- OTP Verification Form for Password Change -->
                        <div style="background:#e7f3ff; padding:15px; border-radius:5px; border:1px solid #b3d9ff; margin-bottom:20px; font-size:14px; color:#004085;">
                            <strong>📧 Verification Required:</strong> A 6-digit code has been sent to your email. Please enter it to confirm password change.
                            <br><small style="display:block; margin-top:8px;">⏱️ Code expires in <strong>10 minutes</strong> from when it was sent.</small>
                        </div>
                        
                        <form method="POST" class="edit-form">
                            <input type="hidden" name="action" value="verify_password_otp">
                            
                            <div class="form-group">
                                <label for="password_otp">Verification Code</label>
                                <input 
                                    type="text" 
                                    id="password_otp" 
                                    name="password_otp" 
                                    placeholder="6-digit code"
                                    maxlength="6"
                                    inputmode="numeric"
                                    pattern="[0-9]{6}"
                                    autocomplete="off"
                                    required
                                >
                                <small style="color: #999;">Enter the code from your email</small>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-primary">Verify & Change Password</button>
                                <button type="reset" class="btn btn-cancel" onclick="location.reload();">Cancel</button>
                            </div>
                        </form>

                        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Didn't receive the code?</p>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="resend_password_otp">
                                <button type="submit" class="btn" style="background: #6c757d; padding: 10px 20px;">🔄 Resend Code</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Password Change Form -->
                        <form method="POST" class="edit-form">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="currentPassword">Current Password</label>
                                <input type="password" id="currentPassword" name="currentPassword" placeholder="Enter your current password" required>
                            </div>

                            <div class="form-group">
                                <label for="newPassword">New Password</label>
                                <input type="password" id="newPassword" name="newPassword" placeholder="Enter new password (minimum 8 characters)" required>
                                <small style="color: #999;">Must be at least 8 characters</small>
                                <div id="newPasswordStrength"></div>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword">Confirm New Password</label>
                                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password" required>
                            </div>

                            <div class="form-buttons">
                                <button type="submit" class="btn btn-primary">Change Password</button>
                                <button type="reset" class="btn btn-cancel" onclick="location.reload();">Cancel</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <div style="margin-top: 20px; padding: 15px; background: #f0f7ff; border-radius: 5px; border-left: 4px solid #667eea;">
                    <p style="color: #333; margin: 0;">
                        <strong>Security Tip:</strong> Keep your password strong and unique. Never share your login credentials with anyone.
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="profile-section">
                <h2>Quick Actions</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <a href="add-money.php" style="padding: 15px; background: #667eea; color: white; text-align: center; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#764ba2'" onmouseout="this.style.background='#667eea'">
                        💰 Add Money
                    </a>
                    <a href="invest.php" style="padding: 15px; background: #667eea; color: white; text-align: center; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#764ba2'" onmouseout="this.style.background='#667eea'">
                        📈 Invest Now
                    </a>
                    <a href="dashboard.php" style="padding: 15px; background: #667eea; color: white; text-align: center; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#764ba2'" onmouseout="this.style.background='#667eea'">
                        📊 Dashboard
                    </a>
                    <a href="contact-us.php" style="padding: 15px; background: #667eea; color: white; text-align: center; border-radius: 5px; text-decoration: none; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#764ba2'" onmouseout="this.style.background='#667eea'">
                        📧 Support
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Account Actions -->
        <div class="profile-section">
            <h2>Account Actions</h2>
            <div style="padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107; margin-bottom: 20px;">
                <p style="color: #856404; margin: 0;">
                    <strong>Note:</strong> You can log out from the navigation menu at the top right.
                </p>
            </div>
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
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="invest.php">Invest</a></li>
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
