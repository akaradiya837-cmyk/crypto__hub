<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_helpers.php';

// Allow normal user login, or admin viewing user-mode via ?as_admin=1
require_user_or_admin_view();

// If admin is viewing user mode, set a JS flag so script.js doesn't redirect
if (is_admin() && !isset($_SESSION['user_email']) && !empty($_GET['as_admin']) && $_GET['as_admin'] == '1') {
    // Simulate a user session for JS
    $_SESSION['user_email'] = 'admin@crypto.com';
    $_SESSION['user_name'] = 'Admin UserMode';
}

// Get real-time user data from database
$userEmail = $_SESSION['user_email'] ?? '';
$userBalance = getUserBalance($userEmail);
$totalInvested = getUserTotalInvestments($userEmail);
$recentTransactions = getUserTransactions($userEmail, 5);
$userInvestments = getUserInvestments($userEmail);
$allCryptos = getAllCryptocurrencies();

// Calculate portfolio stats
$totalValue = $userBalance + $totalInvested;
$profit = $totalValue - $totalInvested; // Simplified
$roi = ($totalInvested > 0) ? (($profit / $totalInvested) * 100) : 0;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CryptoHub</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <ul class="nav-menu">
                    <?php $as_admin = !empty($_GET['as_admin']) ? '?as_admin=1' : ''; ?>
                    <li><a href="home.php<?= $as_admin ?>" class="nav-link">Home</a></li>
                    <li><a href="dashboard.php<?= $as_admin ?>" class="nav-link active">Dashboard</a></li>
                    <li><a href="add-money.php<?= $as_admin ?>" class="nav-link">Add Money</a></li>
                    <li><a href="invest.php<?= $as_admin ?>" class="nav-link">Invest</a></li>
                    <li><a href="contact-us.php<?= $as_admin ?>" class="nav-link">Contact Us</a></li>
                    <li><a href="profile.php<?= $as_admin ?>" class="nav-link">Profile</a></li>
                    <?php if (is_admin() && empty($_GET['as_admin'])): ?>
                        <li><a href="admin-panel.php" class="nav-link" style="color: #FFD700;">🔐 Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="nav-link" id="logoutBtn">Logout</a></li>
                </ul>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <?php if (is_admin() && (!empty($_GET['as_admin']) && $_GET['as_admin'] == '1')): ?>
            <div class="admin-view-banner" style="background:#fff3cd;padding:10px;border:1px solid #ffeeba;margin:10px;border-radius:4px;">
                <strong>Viewing as Admin:</strong> You are viewing the user dashboard in admin mode.
            </div>
        <?php endif; ?>
        <div class="welcome-section">
            <h2>Welcome, <span id="userName"><?=htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'User') ?></span></h2>
            <p id="lastLogin">Last login: Just now</p>
        </div>

        <div class="portfolio-summary">
            <div class="summary-card">
                <h3>Total Balance</h3>
                <p class="amount" id="totalBalance">$<?php echo number_format($userBalance, 2); ?></p>
            </div>
            <div class="summary-card">
                <h3>Total Investment</h3>
                <p class="amount" id="totalInvestment">$<?php echo number_format($totalInvested, 2); ?></p>
            </div>
            <div class="summary-card">
                <h3>Total Profit/Loss</h3>
                <p class="amount <?php echo $profit >= 0 ? 'profit' : 'loss'; ?>" id="totalProfit"><?php echo $profit >= 0 ? '+' : ''; ?>$<?php echo number_format($profit, 2); ?></p>
            </div>
            <div class="summary-card">
                <h3>ROI</h3>
                <p class="amount" id="totalROI"><?php echo number_format($roi, 2); ?>%</p>
            </div>
        </div>


        <div class="charts-container">
            <div class="chart-box">
                <h3>Bitcoin Price (Last 7 Days)</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="bitcoinChart"></canvas>
                </div>
            </div>
            <div class="chart-box">
                <h3>Ethereum Price (Last 7 Days)</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="ethereumChart"></canvas>
                </div>
            </div>
            <div class="chart-box">
                <h3>Portfolio Distribution</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="portfolioChart"></canvas>
                </div>
            </div>
            <div class="chart-box">
                <h3>Top Cryptocurrencies</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="topCryptoChart"></canvas>
                </div>
            </div>
        </div>

        <div class="transactions-section">
            <h3>Recent Transactions</h3>
            <div class="transactions-list" id="transactionsList">
                <?php if (!empty($recentTransactions)): ?>
                    <?php foreach ($recentTransactions as $txn): ?>
                        <div class="transaction-item">
                            <span><?php echo htmlspecialchars(ucfirst($txn['type'])); ?></span>
                            <span class="<?php echo ($txn['type'] === 'deposit' || strpos($txn['type'], 'deposit') !== false) ? 'positive' : 'negative'; ?>">
                                <?php echo ($txn['type'] === 'deposit' || strpos($txn['type'], 'deposit') !== false) ? '+' : '-'; ?>$<?php echo number_format(abs($txn['amount']), 2); ?>
                            </span>
                            <span class="date"><?php echo date('Y-m-d', strtotime($txn['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="transaction-item">
                        <span>No transactions yet</span>
                        <span>-</span>
                        <span class="date">-</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="investments-section" style="margin-top: 40px;">
            <h3>Your Investments</h3>
            <div class="investments-list">
                <?php if (!empty($userInvestments)): ?>
                    <?php foreach ($userInvestments as $inv): ?>
                        <div class="investment-item" style="padding: 15px; border: 1px solid #ddd; margin: 10px 0; border-radius: 5px;">
                            <span><strong><?php echo htmlspecialchars($inv['cryptocurrency']); ?></strong></span>
                            <span>Amount: $<?php echo number_format($inv['amount_invested'], 2); ?></span>
                            <span>Coins: <?php echo number_format($inv['coins_purchased'], 8); ?></span>
                            <span>Type: <?php echo htmlspecialchars(ucfirst($inv['investment_type'])); ?></span>
                            <span class="date">Invested: <?php echo date('Y-m-d', strtotime($inv['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="investment-item" style="padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                        <span>No active investments yet. <a href="invest.php">Start investing now!</a></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>


    <script>
        // Expose server-side session info and real crypto data to client JS
        window.serverUser = {
            email: <?= json_encode($_SESSION['user_email'] ?? '') ?>,
            name: <?= json_encode($_SESSION['user_name'] ?? '') ?>
        };
        
        // Real-time cryptocurrency data from database
        window.cryptocurrencies = <?= json_encode($allCryptos); ?>;
        
        // Portfolio data
        window.portfolio = {
            balance: <?= $userBalance; ?>,
            totalInvested: <?= $totalInvested; ?>,
            profit: <?= $profit; ?>,
            roi: <?= $roi; ?>
        };

        // Chart configuration
        const chartConfig = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: true,
                    labels: { font: { size: 12 } }
                }
            }
        };

        // Initialize charts after DOM and Chart.js library are ready
        function initializeCharts() {
            try {
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js library not loaded');
                    return;
                }

                // Bitcoin Chart
                const bitcoinCanvas = document.getElementById('bitcoinChart');
                if (bitcoinCanvas) {
                    const bitcoinCtx = bitcoinCanvas.getContext('2d');
                    new Chart(bitcoinCtx, {
                        type: 'line',
                        data: {
                            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                            datasets: [{
                                label: 'BTC Price (USD)',
                                data: [32000, 32500, 31800, 33200, 34100, 35200, 36500],
                                borderColor: '#F7931A',
                                backgroundColor: 'rgba(247, 147, 26, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            }]
                        },
                        options: {
                            ...chartConfig,
                            scales: {
                                y: { beginAtZero: false, ticks: { callback: v => '$' + v.toLocaleString() } }
                            }
                        }
                    });
                }

                // Ethereum Chart
                const ethereumCanvas = document.getElementById('ethereumChart');
                if (ethereumCanvas) {
                    const ethereumCtx = ethereumCanvas.getContext('2d');
                    new Chart(ethereumCtx, {
                        type: 'line',
                        data: {
                            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                            datasets: [{
                                label: 'ETH Price (USD)',
                                data: [1800, 1850, 1900, 2000, 2150, 2200, 2300],
                                borderColor: '#627EEA',
                                backgroundColor: 'rgba(98, 126, 234, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            }]
                        },
                        options: {
                            ...chartConfig,
                            scales: {
                                y: { beginAtZero: false, ticks: { callback: v => '$' + v.toLocaleString() } }
                            }
                        }
                    });
                }

                // Portfolio Distribution Chart
                const portfolioCanvas = document.getElementById('portfolioChart');
                if (portfolioCanvas) {
                    const portfolioCtx = portfolioCanvas.getContext('2d');
                    new Chart(portfolioCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Bitcoin', 'Ethereum', 'Ripple', 'Litecoin'],
                            datasets: [{
                                data: [50, 30, 15, 5],
                                backgroundColor: ['#F7931A', '#627EEA', '#23F7DD', '#EACB6B'],
                                borderColor: '#fff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            ...chartConfig,
                            plugins: {
                                ...chartConfig.plugins,
                                legend: { position: 'bottom' }
                            }
                        }
                    });
                }

                // Top Cryptocurrencies Chart
                const topCryptoCanvas = document.getElementById('topCryptoChart');
                if (topCryptoCanvas) {
                    const topCryptoCtx = topCryptoCanvas.getContext('2d');
                    new Chart(topCryptoCtx, {
                        type: 'bar',
                        data: {
                            labels: ['Bitcoin', 'Ethereum', 'Cardano', 'Solana', 'Ripple'],
                            datasets: [{
                                label: 'Market Cap (Billions)',
                                data: [525, 210, 85, 32, 28],
                                backgroundColor: ['#F7931A', '#627EEA', '#0033FE', '#00D4AA', '#23F7DD'],
                                borderRadius: 5,
                                borderSkipped: false
                            }]
                        },
                        options: {
                            ...chartConfig,
                            indexAxis: 'y',
                            scales: {
                                x: { ticks: { callback: v => '$' + v + 'B' } }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error initializing charts:', error);
            }
        }

        // Load user data from database (PHP already provided via variables above)
        function loadUserDashboard() {
            // Get data from window.portfolio set from PHP
            const balance = window.portfolio.balance || 0;
            const investment = window.portfolio.totalInvested || 0;
            const profit = window.portfolio.profit || 0;
            
            if (investment > 0) {
                const roi = ((profit / investment) * 100).toFixed(2);
                document.getElementById('totalROI').textContent = roi + '%';
            }
        }

        // Initialize on page load
        window.addEventListener('load', () => {
            loadUserDashboard();
            initializeCharts();
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
</body>
</html>
