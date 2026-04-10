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
    <title>Transactions - Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar admin-navbar">
        <div class="nav-container">
            <a href="admin-panel.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <li><a href="admin-panel.php" class="nav-link">Dashboard</a></li>
                <li><a href="admin-users.php" class="nav-link">Users</a></li>
                <li><a href="admin-transactions.php" class="nav-link active">Transactions</a></li>
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
                <li><a href="admin-users.php" class="sidebar-link">👥 Manage Users</a></li>
                <li><a href="admin-transactions.php" class="sidebar-link active">💳 Transactions</a></li>
                <li><a href="admin-reports.php" class="sidebar-link">📈 Reports</a></li>
                <li><a href="admin-cryptocurrencies.php" class="sidebar-link">🪙 Cryptocurrencies</a></li>
                <li><a href="admin-settings.php" class="sidebar-link">⚙️ Settings</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Transaction Management</h1>
                <p>View and manage all platform transactions</p>
            </div>

            <div class="admin-section">
                <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                    <input type="text" placeholder="Search transaction ID..." style="flex: 1; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px; min-width: 200px;">
                    <select style="padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px; min-width: 150px;">
                        <option>Filter by Status</option>
                        <option>Completed</option>
                        <option>Pending</option>
                        <option>Failed</option>
                    </select>
                    <select style="padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px; min-width: 150px;">
                        <option>Filter by Type</option>
                        <option>Deposit</option>
                        <option>Withdrawal</option>
                        <option>Trade</option>
                        <option>Investment</option>
                    </select>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>User Name</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>From/To</th>
                            <th>Timestamp</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody">
                        <tr>
                            <td>#TX001</td>
                            <td>John Doe</td>
                            <td>Deposit</td>
                            <td>1000.00</td>
                            <td>USD</td>
                            <td>Bank → Wallet</td>
                            <td>2024-01-15 10:30</td>
                            <td><span class="badge badge-success">Completed</span></td>
                            <td><button class="btn-small btn-view" onclick="viewTransaction('TX001', 'John Doe', 'Deposit', '1000.00', 'USD', 'Bank → Wallet', '2024-01-15 10:30', 'Completed')">View</button></td>
                        </tr>
                        <tr>
                            <td>#TX002</td>
                            <td>Jane Smith</td>
                            <td>Purchase</td>
                            <td>0.025</td>
                            <td>BTC</td>
                            <td>Wallet → Crypto</td>
                            <td>2024-01-15 09:15</td>
                            <td><span class="badge badge-success">Completed</span></td>
                            <td><button class="btn-small btn-view" onclick="viewTransaction('TX002', 'Jane Smith', 'Purchase', '0.025', 'BTC', 'Wallet → Crypto', '2024-01-15 09:15', 'Completed')">View</button></td>
                        </tr>
                        <tr>
                            <td>#TX003</td>
                            <td>Mike Johnson</td>
                            <td>Withdrawal</td>
                            <td>500.00</td>
                            <td>USD</td>
                            <td>Wallet → Bank</td>
                            <td>2024-01-14 14:45</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td><button class="btn-small btn-view" onclick="viewTransaction('TX003', 'Mike Johnson', 'Withdrawal', '500.00', 'USD', 'Wallet → Bank', '2024-01-14 14:45', 'Pending')">View</button></td>
                        </tr>
                        <tr>
                            <td>#TX004</td>
                            <td>Sarah Williams</td>
                            <td>Investment</td>
                            <td>5000.00</td>
                            <td>USD</td>
                            <td>Wallet → Investment</td>
                            <td>2024-01-14 13:20</td>
                            <td><span class="badge badge-success">Completed</span></td>
                            <td><button class="btn-small btn-view" onclick="viewTransaction('TX004', 'Sarah Williams', 'Investment', '5000.00', 'USD', 'Wallet → Investment', '2024-01-14 13:20', 'Completed')">View</button></td>
                        </tr>
                        <tr>
                            <td>#TX005</td>
                            <td>David Lee</td>
                            <td>Transfer</td>
                            <td>0.5</td>
                            <td>ETH</td>
                            <td>Crypto → Wallet</td>
                            <td>2024-01-14 11:00</td>
                            <td><span class="badge" style="background: #f8d7da; color: #721c24;">Failed</span></td>
                            <td><button class="btn-small btn-view" onclick="viewTransaction('TX005', 'David Lee', 'Transfer', '0.5', 'ETH', 'Crypto → Wallet', '2024-01-14 11:00', 'Failed')">View</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-section">
                <h2>Transaction Summary</h2>
                <div class="admin-page-grid">
                    <div class="admin-page-card">
                        <h3>Total Transactions</h3>
                        <p style="font-size: 24px; font-weight: 700; color: #667eea;">45,234</p>
                        <p style="color: #999; font-size: 12px;">All time</p>
                    </div>
                    <div class="admin-page-card">
                        <h3>Total Volume</h3>
                        <p style="font-size: 24px; font-weight: 700; color: #27ae60;">$2,345,678</p>
                        <p style="color: #999; font-size: 12px;">USD Equivalent</p>
                    </div>
                    <div class="admin-page-card">
                        <h3>Today's Transactions</h3>
                        <p style="font-size: 24px; font-weight: 700; color: #F7931A;">324</p>
                        <p style="color: #999; font-size: 12px;">Last 24 hours</p>
                    </div>
                    <div class="admin-page-card">
                        <h3>Pending Transactions</h3>
                        <p style="font-size: 24px; font-weight: 700; color: #e74c3c;">12</p>
                        <p style="color: #999; font-size: 12px;">Requires review</p>
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
        function viewTransaction(txId, userName, type, amount, currency, fromTo, timestamp, status) {
            const modal = document.createElement('div');
            modal.id = 'transactionModal';
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
            
            const statusBadgeColor = status === 'Completed' ? '#27ae60' : status === 'Pending' ? '#F7931A' : '#e74c3c';
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 10px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h2 style="margin: 0; color: #333;">Transaction Details</h2>
                        <button onclick="document.getElementById('transactionModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">×</button>
                    </div>
                    
                    <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <div style="display: grid; gap: 15px;">
                            <div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Transaction ID</p>
                                <p style="margin: 8px 0 0 0; font-size: 16px; font-weight: 600; color: #333;">#${txId}</p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">User Name</p>
                                <p style="margin: 8px 0 0 0; font-size: 16px; color: #333;">${userName}</p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Transaction Type</p>
                                <p style="margin: 8px 0 0 0; font-size: 16px; color: #333;">${type}</p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Amount</p>
                                <p style="margin: 8px 0 0 0; font-size: 18px; font-weight: 700; color: #667eea;">${amount} ${currency}</p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">From / To</p>
                                <p style="margin: 8px 0 0 0; font-size: 16px; color: #333;">${fromTo}</p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Timestamp</p>
                                <p style="margin: 8px 0 0 0; font-size: 16px; color: #333;">${timestamp}</p>
                            </div>
                            
                            <div>
                                <p style="margin: 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Status</p>
                                <p style="margin: 8px 0 0 0;"><span style="background: ${statusBadgeColor}; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">${status}</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <button onclick="document.getElementById('transactionModal').remove()" style="width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600;">Close</button>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
    </script>
