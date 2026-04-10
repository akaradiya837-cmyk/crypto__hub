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
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar admin-navbar">
        <div class="nav-container">
            <a href="admin-panel.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <li><a href="admin-panel.php" class="nav-link">Dashboard</a></li>
                <li><a href="admin-users.php" class="nav-link">Users</a></li>
                <li><a href="admin-transactions.php" class="nav-link">Transactions</a></li>
                <li><a href="admin-reports.php" class="nav-link active">Reports</a></li>
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
                <li><a href="admin-transactions.php" class="sidebar-link">💳 Transactions</a></li>
                <li><a href="admin-reports.php" class="sidebar-link active">📈 Reports</a></li>
                <li><a href="admin-cryptocurrencies.php" class="sidebar-link">🪙 Cryptocurrencies</a></li>
                <li><a href="admin-settings.php" class="sidebar-link">⚙️ Settings</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Reports & Analytics</h1>
                <p>Generate and view platform reports</p>
            </div>

            <div class="admin-section">
                <h2>Report Generator</h2>
                <div class="admin-page-grid">
                    <div class="admin-page-card">
                        <h3>📊 Revenue Report</h3>
                        <p style="color: #666; margin: 10px 0;">Generate detailed revenue reports by period and cryptocurrency</p>
                        <select style="width: 100%; padding: 8px; margin: 10px 0; border: 2px solid #e0e0e0; border-radius: 5px;">
                            <option>Last 7 Days</option>
                            <option>Last 30 Days</option>
                            <option>Last 90 Days</option>
                            <option>Year to Date</option>
                        </select>
                        <button class="btn btn-admin" style="width: 100%; margin-top: 10px;">Generate Report</button>
                    </div>

                    <div class="admin-page-card">
                        <h3>👥 User Report</h3>
                        <p style="color: #666; margin: 10px 0;">Analyze user growth and activity metrics</p>
                        <select style="width: 100%; padding: 8px; margin: 10px 0; border: 2px solid #e0e0e0; border-radius: 5px;">
                            <option>Monthly Growth</option>
                            <option>Weekly Growth</option>
                            <option>Daily Growth</option>
                            <option>By Region</option>
                        </select>
                        <button class="btn btn-admin" style="width: 100%; margin-top: 10px;">Generate Report</button>
                    </div>

                    <div class="admin-page-card">
                        <h3>💳 Transaction Report</h3>
                        <p style="color: #666; margin: 10px 0;">Detailed transaction volume and success rates</p>
                        <select style="width: 100%; padding: 8px; margin: 10px 0; border: 2px solid #e0e0e0; border-radius: 5px;">
                            <option>By Type</option>
                            <option>By Status</option>
                            <option>By User</option>
                            <option>By Currency</option>
                        </select>
                        <button class="btn btn-admin" style="width: 100%; margin-top: 10px;">Generate Report</button>
                    </div>

                    <div class="admin-page-card">
                        <h3>📈 Performance Report</h3>
                        <p style="color: #666; margin: 10px 0;">Platform performance and system health metrics</p>
                        <select style="width: 100%; padding: 8px; margin: 10px 0; border: 2px solid #e0e0e0; border-radius: 5px;">
                            <option>Last 7 Days</option>
                            <option>Last 30 Days</option>
                            <option>Last Quarter</option>
                            <option>Year Overview</option>
                        </select>
                        <button class="btn btn-admin" style="width: 100%; margin-top: 10px;">Generate Report</button>
                    </div>
                </div>
            </div>

            <div class="admin-grid">
                <div class="admin-chart-box">
                    <h3>Monthly Revenue Trend</h3>
                    <canvas id="revenueChart"></canvas>
                </div>

                <div class="admin-chart-box">
                    <h3>User Acquisition</h3>
                    <canvas id="userChart"></canvas>
                </div>

                <div class="admin-chart-box">
                    <h3>Transaction Types Distribution</h3>
                    <canvas id="transactionDistChart"></canvas>
                </div>

                <div class="admin-chart-box">
                    <h3>Platform Load (Last 24 Hours)</h3>
                    <canvas id="loadChart"></canvas>
                </div>
            </div>

            <div class="admin-section">
                <h2>Custom Report</h2>
                <form style="display: grid; gap: 15px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Start Date:</label>
                            <input type="date" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">End Date:</label>
                            <input type="date" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px;">
                        </div>
                    </div>
                    <button type="button" class="btn btn-admin" onclick="alert('Custom report generation initiated')">Generate Custom Report</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>Scheduled Reports</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Report Name</th>
                            <th>Frequency</th>
                            <th>Last Generated</th>
                            <th>Next Scheduled</th>
                            <th>Recipients</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Daily Revenue Report</td>
                            <td>Daily</td>
                            <td>2024-01-15 23:59</td>
                            <td>2024-01-16 23:59</td>
                            <td>admin@cryptohub.com</td>
                            <td><button class="btn-small btn-view">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Weekly User Report</td>
                            <td>Weekly</td>
                            <td>2024-01-14 00:00</td>
                            <td>2024-01-21 00:00</td>
                            <td>management@cryptohub.com</td>
                            <td><button class="btn-small btn-view">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Monthly Performance Report</td>
                            <td>Monthly</td>
                            <td>2024-01-01 00:00</td>
                            <td>2024-02-01 00:00</td>
                            <td>director@cryptohub.com</td>
                            <td><button class="btn-small btn-view">Edit</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        function initializeReportCharts() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Revenue ($)',
                        data: [25000, 28000, 32000, 35000, 38000, 42000, 45000, 48000, 52000, 55000, 58000, 62000],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: true } } }
            });

            // User Chart
            const userCtx = document.getElementById('userChart').getContext('2d');
            new Chart(userCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'New Users',
                        data: [150, 180, 220, 250, 280, 320],
                        backgroundColor: '#764ba2'
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });

            // Transaction Distribution
            const transCtx = document.getElementById('transactionDistChart').getContext('2d');
            new Chart(transCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Deposits', 'Withdrawals', 'Trades', 'Investments', 'Transfers'],
                    datasets: [{
                        data: [30, 20, 25, 15, 10],
                        backgroundColor: ['#667eea', '#764ba2', '#F7931A', '#27ae60', '#3498db']
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });

            // Load Chart
            const loadCtx = document.getElementById('loadChart').getContext('2d');
            new Chart(loadCtx, {
                type: 'line',
                data: {
                    labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'],
                    datasets: [{
                        label: 'CPU Usage %',
                        data: [45, 52, 68, 85, 72, 58, 48],
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: { responsive: true }
            });
        }

        window.addEventListener('load', initializeReportCharts);
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
