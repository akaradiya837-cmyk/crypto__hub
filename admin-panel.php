<?php
require_once __DIR__ . '/auth.php';
require_admin_login();

// Resolve admin identity (DB-aware)
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
    <title>Admin Panel - CryptoHub</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar admin-navbar">
        <div class="nav-container">
            <a href="admin-panel.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <li><a href="admin-panel.php" class="nav-link active">Dashboard</a></li>
                <li><a href="admin-users.php" class="nav-link">Users</a></li>
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
                <p id="adminDept">Department</p>
            </div>

            <ul class="sidebar-menu">
                <li><a href="admin-panel.php" class="sidebar-link active">Dashboard</a></li>
                <li><a href="admin-users.php" class="sidebar-link">Manage Users</a></li>
                <li><a href="admin-transactions.php" class="sidebar-link">💳 Transactions</a></li>
                <li><a href="admin-reports.php" class="sidebar-link">📈 Reports</a></li>
                <li><a href="admin-cryptocurrencies.php" class="sidebar-link">🪙 Cryptocurrencies</a></li>
                <li><a href="admin-settings.php" class="sidebar-link">⚙️ Settings</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome, <span id="welcomeAdminName"><?= htmlspecialchars($admin_name) ?></span>!</p>
            </div>

            <div class="admin-stats">
                <div class="stat-card stat-users">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p class="stat-number" id="totalUsers">0</p>
                        <p class="stat-change">+12% from last month</p>
                    </div>
                </div>

                <div class="stat-card stat-transactions">
                    <div class="stat-icon">💳</div>
                    <div class="stat-info">
                        <h3>Total Transactions</h3>
                        <p class="stat-number" id="totalTransactions">0</p>
                        <p class="stat-change">+8.5% from last month</p>
                    </div>
                </div>

                <div class="stat-card stat-volume">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>Trading Volume</h3>
                        <p class="stat-number" id="tradingVolume">$0</p>
                        <p class="stat-change">+15% from last month</p>
                    </div>
                </div>

                <div class="stat-card stat-revenue">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3>Platform Revenue</h3>
                        <p class="stat-number" id="platformRevenue">$0</p>
                        <p class="stat-change">+20% from last month</p>
                    </div>
                </div>
            </div>

            <div class="admin-grid">
                <div class="admin-chart-box">
                    <h3>User Growth (Last 6 Months)</h3>
                    <canvas id="userGrowthChart"></canvas>
                </div>

                <div class="admin-chart-box">
                    <h3>Transaction Distribution</h3>
                    <canvas id="transactionChart"></canvas>
                </div>

                <div class="admin-chart-box">
                    <h3>Revenue by Cryptocurrency</h3>
                    <canvas id="revenueChart"></canvas>
                </div>

                <div class="admin-chart-box">
                    <h3>Platform Activity</h3>
                    <canvas id="activityChart"></canvas>
                </div>
            </div>

            <div class="admin-section">
                <h2>Recent Users</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="recentUsersBody">
                        <tr>
                            <td>#001</td>
                            <td>John Doe</td>
                            <td>john@examplez.com</td>
                            <td>$5,234.50</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>2024-01-15</td>
                            <td><button class="btn-small btn-view">View</button></td>
                        </tr>
                        <tr>
                            <td>#002</td>
                            <td>Jane Smith</td>
                            <td>jane@example.com</td>
                            <td>$12,450.75</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>2024-01-14</td>
                            <td><button class="btn-small btn-view">View</button></td>
                        </tr>
                        <tr>
                            <td>#003</td>
                            <td>Mike Johnson</td>
                            <td>mike@example.com</td>
                            <td>$0.00</td>
                            <td><span class="badge badge-inactive">Inactive</span></td>
                            <td>2024-01-10</td>
                            <td><button class="btn-small btn-view">View</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-section">
                <h2>Recent Transactions</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Timestamp</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentTransactionsBody">
                        <tr>
                            <td>#TX001</td>
                            <td>John Doe</td>
                            <td>Deposit</td>
                            <td>1000.00</td>
                            <td>USD</td>
                            <td>2024-01-15 10:30</td>
                            <td><span class="badge badge-success">Completed</span></td>
                        </tr>
                        <tr>
                            <td>#TX002</td>
                            <td>Jane Smith</td>
                            <td>Purchase BTC</td>
                            <td>0.025</td>
                            <td>BTC</td>
                            <td>2024-01-15 09:15</td>
                            <td><span class="badge badge-success">Completed</span></td>
                        </tr>
                        <tr>
                            <td>#TX003</td>
                            <td>Mike Johnson</td>
                            <td>Withdrawal</td>
                            <td>500.00</td>
                            <td>USD</td>
                            <td>2024-01-14 14:45</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        // Initialize admin charts
        function initializeAdminCharts() {
            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'New Users',
                        data: [150, 200, 180, 250, 300, 350],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true }
                    }
                }
            });

            // Transaction Distribution
            const transactionCtx = document.getElementById('transactionChart').getContext('2d');
            new Chart(transactionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Deposits', 'Withdrawals', 'Trades', 'Investments'],
                    datasets: [{
                        data: [35, 25, 30, 10],
                        backgroundColor: ['#667eea', '#764ba2', '#F7931A', '#27ae60']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: ['Bitcoin', 'Ethereum', 'Cardano', 'Solana', 'Ripple'],
                    datasets: [{
                        label: 'Revenue ($)',
                        data: [15000, 12000, 8500, 5200, 4800],
                        backgroundColor: ['#F7931A', '#627EEA', '#0033FE', '#00D4AA', '#23F7DD']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            // Activity Chart
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            new Chart(activityCtx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Active Users',
                        data: [250, 320, 280, 350, 400, 320, 280],
                        backgroundColor: '#667eea'
                    }, {
                        label: 'Transactions',
                        data: [150, 180, 160, 200, 240, 190, 170],
                        backgroundColor: '#764ba2'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true }
                    }
                }
            });
        }

        // Load admin data
        function loadAdminData() {
            const adminName = localStorage.getItem('adminName') || 'Admin User';
            const adminRole = localStorage.getItem('adminRole') || 'Administrator';
            const adminDept = localStorage.getItem('adminDepartment') || 'Management';

            document.getElementById('adminName').textContent = adminName;
            document.getElementById('welcomeAdminName').textContent = adminName.split(' ')[0];
            document.getElementById('adminRole').textContent = adminRole;
            document.getElementById('adminDept').textContent = adminDept;

            // Load statistics
            document.getElementById('totalUsers').textContent = (Math.floor(Math.random() * 5000) + 1000).toLocaleString();
            document.getElementById('totalTransactions').textContent = (Math.floor(Math.random() * 50000) + 10000).toLocaleString();
            document.getElementById('tradingVolume').textContent = '$' + (Math.floor(Math.random() * 500000) + 100000).toLocaleString();
            document.getElementById('platformRevenue').textContent = '$' + (Math.floor(Math.random() * 100000) + 50000).toLocaleString();
        }

        window.addEventListener('load', () => {
            loadAdminData();
            initializeAdminCharts();
        });
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
