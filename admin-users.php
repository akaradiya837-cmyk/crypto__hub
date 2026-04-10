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
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar admin-navbar">
        <div class="nav-container">
            <a href="admin-panel.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <li><a href="admin-panel.php" class="nav-link">Dashboard</a></li>
                <li><a href="admin-users.php" class="nav-link active">Users</a></li>
                <li><a href="admin-transactions.php" class="nav-link">Transactions</a></li>
                <li><a href="admin-reports.php" class="nav-link">Reports</a></li>
                <li><a href="admin-cryptocurrencies.php" class="nav-link">🪙 Cryptocurrencies</a></li>
                <li><a href="admin-settings.php" class="nav-link">Settings</a></li>
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
                <li><a href="admin-users.php" class="sidebar-link active">👥 Manage Users</a></li>
                <li><a href="admin-transactions.php" class="sidebar-link">💳 Transactions</a></li>
                <li><a href="admin-reports.php" class="sidebar-link">📈 Reports</a></li>
                <li><a href="admin-cryptocurrencies.php" class="sidebar-link">🪙 Cryptocurrencies</a></li>
                <li><a href="admin-settings.php" class="sidebar-link">⚙️ Settings</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Manage Users</h1>
                <p>View, edit, and manage user accounts</p>
            </div>

            <div class="admin-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>All Users</h2>
                    <button class="btn btn-admin" onclick="showAddUserForm()">+ Add New User</button>
                </div>

                <div style="margin-bottom: 20px;">
                    <input type="text" id="userSearchBox" placeholder="Search users by name or email..." style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px;">
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Account Balance</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td>#001</td>
                            <td>John Doe</td>
                            <td>john@example.com</td>
                            <td>+1234567890</td>
                            <td>$5,234.50</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>2024-01-15</td>
                            <td>
                                <button class="btn-small btn-view" onclick="viewUser('John Doe', 'john@example.com', '+1234567890', '$5,234.50')">👁️ View</button>
                                <button class="btn-small btn-view" style="background: #4CAF50; color: white; margin-left: 5px;" onclick="editUser('John Doe', 'john@example.com', '+1234567890')">✏️ Edit</button>
                            </td>
                        </tr>
                        <tr>
                            <td>#002</td>
                            <td>Jane Smith</td>
                            <td>jane@example.com</td>
                            <td>+0987654321</td>
                            <td>$12,450.75</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>2024-01-14</td>
                            <td>
                                <button class="btn-small btn-view" onclick="viewUser('Jane Smith', 'jane@example.com', '+0987654321', '$12,450.75')">👁️ View</button>
                                <button class="btn-small btn-view" style="background: #4CAF50; color: white; margin-left: 5px;" onclick="editUser('Jane Smith', 'jane@example.com', '+0987654321')">✏️ Edit</button>
                            </td>
                        </tr>
                        <tr>
                            <td>#003</td>
                            <td>Mike Johnson</td>
                            <td>mike@example.com</td>
                            <td>+1122334455</td>
                            <td>$0.00</td>
                            <td><span class="badge badge-inactive">Inactive</span></td>
                            <td>2024-01-10</td>
                            <td>
                                <button class="btn-small btn-view" onclick="viewUser('Mike Johnson', 'mike@example.com', '+1122334455', '$0.00')">👁️ View</button>
                                <button class="btn-small btn-view" style="background: #4CAF50; color: white; margin-left: 5px;" onclick="editUser('Mike Johnson', 'mike@example.com', '+1122334455')">✏️ Edit</button>
                            </td>
                        </tr>
                        <tr>
                            <td>#004</td>
                            <td>Sarah Williams</td>
                            <td>sarah@example.com</td>
                            <td>+5566778899</td>
                            <td>$8,900.25</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>2024-01-05</td>
                            <td>
                                <button class="btn-small btn-view" onclick="viewUser('Sarah Williams', 'sarah@example.com', '+5566778899', '$8,900.25')">👁️ View</button>
                                <button class="btn-small btn-view" style="background: #4CAF50; color: white; margin-left: 5px;" onclick="editUser('Sarah Williams', 'sarah@example.com', '+5566778899')">✏️ Edit</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-section">
                <h2>User Statistics</h2>
                <div class="admin-page-grid">
                    <div class="admin-page-card">
                        <h3>👥 Total Users</h3>
                        <p style="font-size: 24px; font-weight: 700; color: #667eea;">1,234</p>
                        <p style="color: #999; font-size: 12px;">+8% from last month</p>
                    </div>
                    <div class="admin-page-card">
                        <h3>✅ Active Users</h3>
                        <p style="font-size: 24px; font-weight: 700; color: #27ae60;">987</p>
                        <p style="color: #999; font-size: 12px;">80% of total</p>
                    </div>
                    <div class="admin-page-card">
                        <h3>❌ Inactive Users</h3>
                        <p style="font-size: 24px; font-weight: 700; color: #e74c3c;">247</p>
                        <p style="color: #999; font-size: 12px;">20% of total</p>
                    </div>
                    <div class="admin-page-card">
                        <h3>🆕 New Users (7 days)</h3>
                        <p style="font-size: 24px; font-weight: 700; color: #F7931A;">45</p>
                        <p style="color: #999; font-size: 12px;">-5% from previous week</p>
                    </div>
                </div>
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
    <script>
        function viewUser(name, email, phone, balance) {
            const modal = document.createElement('div');
            modal.id = 'userModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 10px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h2 style="margin: 0; color: #333;">User Details</h2>
                        <button onclick="document.getElementById('userModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">×</button>
                    </div>
                    
                    <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <div style="display: grid; gap: 15px;">
                            <div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Full Name</p>
                                <p style="margin: 8px 0 0 0; font-size: 18px; font-weight: 600; color: #333;">${name}</p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Email Address</p>
                                <p style="margin: 8px 0 0 0; font-size: 16px; color: #333;">${email}</p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Phone Number</p>
                                <p style="margin: 8px 0 0 0; font-size: 16px; color: #333;">${phone}</p>
                            </div>
                            
                            <div>
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Account Balance</p>
                                <p style="margin: 8px 0 0 0; font-size: 20px; font-weight: 700; color: #27ae60;">${balance}</p>
                            </div>
                        </div>
                    </div>
                    
                    <button onclick="document.getElementById('userModal').remove()" style="width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600;">Close</button>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        function editUser(name, email, phone) {
            const modal = document.createElement('div');
            modal.id = 'editUserModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 10px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h2 style="margin: 0; color: #333;">Edit User</h2>
                        <button onclick="document.getElementById('editUserModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">×</button>
                    </div>
                    
                    <form style="display: grid; gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Full Name</label>
                            <input type="text" value="${name}" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 16px;" placeholder="Enter full name">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Email Address</label>
                            <input type="email" value="${email}" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 16px;" placeholder="Enter email">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Phone Number</label>
                            <input type="tel" value="${phone}" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 16px;" placeholder="Enter phone number">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px;">
                            <button type="button" onclick="document.getElementById('editUserModal').remove()" style="padding: 12px; background: #e0e0e0; color: #333; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600;">Cancel</button>
                            <button type="button" onclick="saveUserChanges()" style="padding: 12px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600;">Save Changes</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        function saveUserChanges() {
            alert('User changes saved successfully!');
            document.getElementById('editUserModal').remove();
        }
    </script>
