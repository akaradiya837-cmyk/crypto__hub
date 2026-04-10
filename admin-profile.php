<?php
require_once __DIR__ . '/auth.php';
// Require admin login only
require_login();
if (!is_admin()) {
    header('Location: profile.php');
    exit;
}

$usersFile = __DIR__ . '/users.json';
$users = [];
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?? [];
}

$admin_email = $_SESSION['user_email'] ?? 'admin@crypto.com';
$admin_name = $_SESSION['user_name'] ?? 'Administrator';

$message = '';
$message_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_admin_profile') {
    $fullName = trim($_POST['fullName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($fullName)) {
        $message = 'Name cannot be empty.';
        $message_type = 'error';
    } else {
        $updated = false;
        
        // Try DB first (ch_admins table)
        $pdo = getDB();
        if ($pdo) {
            try {
                $upd = $pdo->prepare('UPDATE ch_admins SET full_name = :full_name, phone = :phone WHERE email = :email');
                $upd->execute([
                    ':full_name' => $fullName,
                    ':phone' => $phone,
                    ':email' => $admin_email
                ]);
                if ($upd->rowCount() > 0) {
                    $updated = true;
                }
            } catch (Exception $e) {
                // Continue to JSON fallback
            }
        }
        
        // Fallback to JSON
        if (!$updated) {
            if (isset($users[$admin_email])) {
                $users[$admin_email]['fullName'] = $fullName;
                $users[$admin_email]['phone'] = $phone;
                
                if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    $updated = true;
                }
            } else {
                // Create new entry if doesn't exist
                $users[$admin_email] = [
                    'fullName' => $fullName,
                    'phone' => $phone,
                    'password' => password_hash(uniqid(), PASSWORD_DEFAULT)
                ];
                
                if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    $updated = true;
                }
            }
        }
        
        if ($updated) {
            $_SESSION['user_name'] = $fullName;
            $admin_name = $fullName;
            $message = 'Admin profile updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update profile. Please try again.';
            $message_type = 'error';
            }
        }
    }
}

// Get admin statistics
$total_users = count($users);
$admin_data = isset($users[$admin_email]) ? $users[$admin_email] : [
    'fullName' => $admin_name,
    'phone' => 'Not provided'
];
?>

<?php
require_once __DIR__ . '/auth.php';
require_admin_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - CryptoHub</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-profile-container {
            max-width: 1200px;
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
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 56px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .profile-info h1 {
            color: #333;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .profile-role {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 15px;
            background: #FFD700;
            color: #333;
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
            color: #FFD700;
            font-size: 24px;
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
            border-color: #FFD700;
            box-shadow: 0 0 5px rgba(255, 215, 0, 0.3);
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
            background: #FFD700;
            color: #333;
        }

        .btn-edit:hover {
            background: #FFA500;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            padding: 25px;
            border-radius: 10px;
            color: white;
            text-align: center;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .stat-card h3 {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .stat-card p {
            font-size: 14px;
            opacity: 0.95;
            margin: 0;
        }

        .edit-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .action-btn {
            padding: 15px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: white;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.4);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .metric-box {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #FFD700;
        }

        .metric-box h4 {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .metric-box p {
            color: #333;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
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
            <a href="admin-panel.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <li><a href="admin-panel.php" class="nav-link">Dashboard</a></li>
                <li><a href="admin-users.php" class="nav-link">Users</a></li>
                <li><a href="admin-transactions.php" class="nav-link">Transactions</a></li>
                <li><a href="admin-reports.php" class="nav-link">Reports</a></li>
                <li><a href="admin-settings.php" class="nav-link">Settings</a></li>
                <li><a href="admin-profile.php" class="nav-link active" style="color: #FFD700;">Profile</a></li>
                <li><a href="logout.php" class="nav-link" id="logoutBtn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-profile-container">
        <?php if ($message): ?>
            <div class="alert <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                🔐
            </div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($admin_name) ?></h1>
                <span class="profile-role">🏆 ADMINISTRATOR</span>
                <div class="profile-email">
                    Email: <?= htmlspecialchars($admin_email) ?>
                </div>
                <div class="profile-email">
                    Status: <strong style="color: #28a745;">Active</strong>
                </div>
                <div class="profile-email" style="font-size: 12px; color: #999;">
                    Member since: <?= date('F Y', strtotime('now - ' . rand(3, 24) . ' months')) ?>
                </div>
            </div>
        </div>

        <!-- Admin Statistics Section -->
        <div class="profile-section">
            <h2>📊 Admin Dashboard Overview</h2>
            
            <div class="admin-stats">
                <div class="stat-card">
                    <h3><?= $total_users ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <h3>0</h3>
                    <p>Active Sessions</p>
                </div>
                <div class="stat-card">
                    <h3>0</h3>
                    <p>Pending Transactions</p>
                </div>
                <div class="stat-card">
                    <h3>$0.00</h3>
                    <p>Platform Revenue</p>
                </div>
            </div>

            <div style="margin-top: 30px; padding: 20px; background: #fffacd; border-radius: 8px; border-left: 4px solid #FFD700;">
                <p style="color: #333; margin: 0; font-weight: 600;">
                    You have administrative access to the entire platform. Manage users, transactions, reports, and system settings from the admin panel.
                </p>
            </div>
        </div>

        <!-- Personal Information Section -->
        <div class="profile-section">
            <h2>👤 Personal Information</h2>
            
            <?php if (!isset($_POST['edit_profile'])): ?>
                <div class="edit-toggle">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="edit_profile" value="1" class="btn-edit">✏️ Edit Profile</button>
                    </form>
                </div>

                <div class="profile-field">
                    <label>Full Name</label>
                    <div class="profile-field-value"><?= htmlspecialchars($admin_data['fullName'] ?? 'Administrator') ?></div>
                </div>
                <div class="profile-field">
                    <label>Email Address</label>
                    <div class="profile-field-value"><?= htmlspecialchars($admin_email) ?></div>
                </div>
                <div class="profile-field">
                    <label>Phone Number</label>
                    <div class="profile-field-value"><?= htmlspecialchars($admin_data['phone'] ?? 'Not provided') ?></div>
                </div>

            <?php else: ?>
                <form method="POST" class="edit-form">
                    <input type="hidden" name="action" value="update_admin_profile">
                    
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" value="<?= htmlspecialchars($admin_data['fullName'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="text" id="email" value="<?= htmlspecialchars($admin_email) ?>" readonly style="background: #f0f0f0; cursor: not-allowed;">
                        <small style="color: #999;">Email cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($admin_data['phone'] ?? '') ?>">
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="reset" class="btn btn-cancel" onclick="location.reload();">Cancel</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Administration Tools -->
        <div class="profile-section">
            <h2>🛠️ Administration Tools</h2>
            
            <div class="admin-actions">
                <a href="admin-panel.php" class="action-btn">📊 Admin Dashboard</a>
                <a href="admin-users.php" class="action-btn">👥 Manage Users</a>
                <a href="admin-transactions.php" class="action-btn">💰 Transactions</a>
                <a href="admin-reports.php" class="action-btn">📈 Reports</a>
                <a href="admin-settings.php" class="action-btn">⚙️ Settings</a>
                <a href="contact-us.php?as_admin=1" class="action-btn">📧 Messages</a>
            </div>
        </div>

        <!-- System Metrics -->
        <div class="profile-section">
            <h2>📈 System Metrics</h2>
            
            <div class="metrics-grid">
                <div class="metric-box">
                    <h4>Registered Users</h4>
                    <p><?= $total_users ?></p>
                </div>
                <div class="metric-box">
                    <h4>Total Platform Value</h4>
                    <p>$0.00</p>
                </div>
                <div class="metric-box">
                    <h4>Active Investments</h4>
                    <p>0</p>
                </div>
                <div class="metric-box">
                    <h4>System Uptime</h4>
                    <p>99.9%</p>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="profile-section">
            <h2>🔒 Security Settings</h2>
            
            <div class="profile-field">
                <label>Admin Password</label>
                <div class="profile-field-value" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>••••••••••••••</span>
                    <button type="button" class="btn-edit" style="padding: 6px 12px; font-size: 12px;" onclick="document.getElementById('adminPasswordForm').style.display = 'block'; this.style.display = 'none';">Change Password</button>
                </div>
            </div>

            <!-- Admin Password Change Form -->
            <form method="POST" id="adminPasswordForm" class="edit-form" style="display: none; margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
                <input type="hidden" name="action" value="change_admin_password">
                
                <div class="form-group">
                    <label for="adminCurrentPassword">Current Password</label>
                    <input type="password" id="adminCurrentPassword" name="currentPassword" placeholder="Enter your current password" required>
                </div>

                <div class="form-group">
                    <label for="adminNewPassword">New Password</label>
                    <input type="password" id="adminNewPassword" name="newPassword" placeholder="Enter new password (minimum 8 characters)" required>
                    <small style="color: #999;">Must be at least 8 characters</small>
                    <div id="adminNewPasswordStrength"></div>
                </div>

                <div class="form-group">
                    <label for="adminConfirmPassword">Confirm New Password</label>
                    <input type="password" id="adminConfirmPassword" name="confirmPassword" placeholder="Confirm new password" required>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                    <button type="button" class="btn btn-cancel" onclick="document.getElementById('adminPasswordForm').style.display = 'none'; document.querySelector('.btn-edit').style.display = 'inline-block';">Cancel</button>
                </div>
            </form>

            <div style="margin-top: 20px; padding: 15px; background: #e8f4f8; border-radius: 5px; border-left: 4px solid #007bff;">
                <p style="color: #333; margin: 0;">
                    <strong>Security Reminder:</strong> Keep your admin credentials secure. Never share your login information with anyone.
                </p>
            </div>
        </div>

        <!-- Admin Activities Log -->
        <div class="profile-section">
            <h2>📋 Recent Admin Activities</h2>
            
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9ff; border-bottom: 2px solid #FFD700;">
                        <th style="padding: 12px; text-align: left; color: #333; font-weight: 600;">Activity</th>
                        <th style="padding: 12px; text-align: left; color: #333; font-weight: 600;">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #e0e0e0;">
                        <td style="padding: 12px; color: #666;">Logged into admin panel</td>
                        <td style="padding: 12px; color: #999; font-size: 14px;">Just now</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e0e0e0;">
                        <td style="padding: 12px; color: #666;">System initialized</td>
                        <td style="padding: 12px; color: #999; font-size: 14px;">Today</td>
                    </tr>
                </tbody>
            </table>
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
                        <li><a href="admin-panel.php">Dashboard</a></li>
                        <li><a href="admin-users.php">Users</a></li>
                        <li><a href="admin-transactions.php">Transactions</a></li>
                        <li><a href="admin-reports.php">Reports</a></li>
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
