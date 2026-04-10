<?php
require_once __DIR__ . '/auth.php';
require_admin_login();

// Admin identity (DB-aware)
$admin_email = $_SESSION['admin_email'] ?? $_SESSION['user_email'] ?? null;
$admin = null;
$admin_name = 'Administrator';
if ($admin_email) {
    if (function_exists('find_admin_by_email')) {
        $admin = find_admin_by_email($admin_email);
    }
    if ($admin) {
        $admin_name = $admin['fullName'] ?? $admin['full_name'] ?? $admin_name;
    } else {
        $admin_name = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? $admin_name;
    }
}
$admin_initial = htmlspecialchars(mb_substr($admin_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar admin-navbar">
        <div class="nav-container">
            <a href="admin-panel.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <li><a href="admin-panel.php" class="nav-link">Dashboard</a></li>
                <li><a href="admin-users.php" class="nav-link">Users</a></li>
                <li><a href="admin-transactions.php" class="nav-link">Transactions</a></li>
                <li><a href="admin-reports.php" class="nav-link">Reports</a></li>
                <li><a href="admin-cryptocurrencies.php" class="nav-link">🪙 Cryptocurrencies</a></li>
                <li><a href="admin-settings.php" class="nav-link active">Settings</a></li>
                <li><a href="dashboard.php?as_admin=1" class="nav-link" style="color:#4CAF50;">User Mode</a></li>
                <li><a href="logout.php" class="nav-link" id="adminLogoutBtn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-profile">
                <div class="profile-avatar"><?= $admin_initial ?></div>
                <h3 id="adminName"><?= htmlspecialchars($admin_name) ?></h3>
                <p id="adminRole">Administrator</p>
            </div>

            <ul class="sidebar-menu">
                <li><a href="admin-panel.php" class="sidebar-link">📊 Dashboard</a></li>
                <li><a href="admin-users.php" class="sidebar-link">👥 Manage Users</a></li>
                <li><a href="admin-transactions.php" class="sidebar-link">💳 Transactions</a></li>
                <li><a href="admin-reports.php" class="sidebar-link">📈 Reports</a></li>
                <li><a href="admin-cryptocurrencies.php" class="sidebar-link">🪙 Cryptocurrencies</a></li>
                <li><a href="admin-settings.php" class="sidebar-link active">⚙️ Settings</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Settings</h1>
                <p>Manage platform settings and configuration</p>
            </div>

            <div class="admin-page-grid">
                <div class="admin-page-card">
                    <h3>⚙️ General Settings</h3>
                    <p style="color: #666; margin-bottom: 10px; font-size: 12px;">Configure basic platform settings</p>
                    <a href="#" onclick="showGeneralSettings()" style="color: #667eea; text-decoration: none; font-weight: 600;">Configure →</a>
                </div>

                <div class="admin-page-card">
                    <h3>🔒 Security Settings</h3>
                    <p style="color: #666; margin-bottom: 10px; font-size: 12px;">Manage security and access control</p>
                    <a href="#" onclick="showSecuritySettings()" style="color: #667eea; text-decoration: none; font-weight: 600;">Configure →</a>
                </div>

                <div class="admin-page-card">
                    <h3>💰 Payment Settings</h3>
                    <p style="color: #666; margin-bottom: 10px; font-size: 12px;">Configure payment methods and limits</p>
                    <a href="#" onclick="showPaymentSettings()" style="color: #667eea; text-decoration: none; font-weight: 600;">Configure →</a>
                </div>

                <div class="admin-page-card">
                    <h3>📧 Email Settings</h3>
                    <p style="color: #666; margin-bottom: 10px; font-size: 12px;">Configure email notifications</p>
                    <a href="#" onclick="showEmailSettings()" style="color: #667eea; text-decoration: none; font-weight: 600;">Configure →</a>
                </div>
            </div>

            <div class="admin-section">
                <h2>General Settings</h2>
                <form style="display: grid; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Platform Name:</label>
                        <input type="text" value="CryptoHub" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Support Email:</label>
                        <input type="email" value="support@cryptohub.com" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Commission Rate (%):</label>
                        <input type="number" value="2.5" step="0.1" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Maintenance Mode:</label>
                        <select style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px;">
                            <option>Off</option>
                            <option>On</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-admin" onclick="alert('Settings saved successfully')">Save Changes</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>Admin Users</h2>
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-admin">+ Add New Admin</button>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Privilege Level</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sarah Admin</td>
                            <td>sarah.admin@cryptohub.com</td>
                            <td>Management</td>
                            <td><span class="badge" style="background: #ffd4d4; color: #c0392b;">Manager</span></td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>2024-01-01</td>
                            <td>
                                <button class="btn-small btn-view" onclick="editAdmin('Sarah Admin', 'sarah.admin@cryptohub.com')">Edit</button>
                                <button class="btn-small" style="background: #e74c3c; color: white; margin-left: 5px;" onclick="toggleAdminStatus(this, 'Sarah Admin', true)">Deactivate</button>
                            </td>
                        </tr>
                        <tr>
                            <td>John Support</td>
                            <td>john.support@cryptohub.com</td>
                            <td>Support</td>
                            <td><span class="badge" style="background: #d4e6f1; color: #2874a6;">Editor</span></td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>2024-01-05</td>
                            <td>
                                <button class="btn-small btn-view" onclick="editAdmin('John Support', 'john.support@cryptohub.com')">Edit</button>
                                <button class="btn-small" style="background: #e74c3c; color: white; margin-left: 5px;" onclick="toggleAdminStatus(this, 'John Support', true)">Deactivate</button>
                            </td>
                        </tr>
                        <tr>
                            <td>Mike Security</td>
                            <td>mike.security@cryptohub.com</td>
                            <td>Security</td>
                            <td><span class="badge" style="background: #d4edda; color: #155724;">Viewer</span></td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>2024-01-10</td>
                            <td>
                                <button class="btn-small btn-view" onclick="editAdmin('Mike Security', 'mike.security@cryptohub.com')">Edit</button>
                                <button class="btn-small" style="background: #e74c3c; color: white; margin-left: 5px;" onclick="toggleAdminStatus(this, 'Mike Security', true)">Deactivate</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-section">
                <h2>Activity Log</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Timestamp</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sarah Admin</td>
                            <td>User Suspended</td>
                            <td>Suspended user #234</td>
                            <td>2024-01-15 14:30</td>
                            <td>192.168.1.1</td>
                        </tr>
                        <tr>
                            <td>John Support</td>
                            <td>Transaction Refunded</td>
                            <td>Refunded TX #TX045</td>
                            <td>2024-01-15 13:15</td>
                            <td>192.168.1.5</td>
                        </tr>
                        <tr>
                            <td>Mike Security</td>
                            <td>Settings Updated</td>
                            <td>Commission rate changed</td>
                            <td>2024-01-15 12:00</td>
                            <td>192.168.1.8</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-section">
                <h2>System Maintenance</h2>
                <div class="admin-page-grid">
                    <div class="admin-page-card">
                        <h3>💾 Database Backup</h3>
                        <p style="color: #666; margin-bottom: 15px; font-size: 12px;">Last backup: 2024-01-15 23:00</p>
                        <button class="btn btn-admin" style="width: 100%;" onclick="alert('Backup initiated...')">Run Backup</button>
                    </div>

                    <div class="admin-page-card">
                        <h3>🔍 System Check</h3>
                        <p style="color: #666; margin-bottom: 15px; font-size: 12px;">Check system health and performance</p>
                        <button class="btn btn-admin" style="width: 100%;" onclick="alert('Running system diagnostics...')">Run Check</button>
                    </div>

                    <div class="admin-page-card">
                        <h3>🗑️ Clear Cache</h3>
                        <p style="color: #666; margin-bottom: 15px; font-size: 12px;">Clear application cache</p>
                        <button class="btn btn-admin" style="width: 100%;" onclick="alert('Cache cleared successfully')">Clear Cache</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        function showGeneralSettings() {
            alert('Opening General Settings...');
        }
        function showSecuritySettings() {
            alert('Opening Security Settings...');
        }
        function showPaymentSettings() {
            alert('Opening Payment Settings...');
        }
        function showEmailSettings() {
            alert('Opening Email Settings...');
        }
    </script>

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
</body>
</html>
