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

// Get all available cryptocurrencies from database
$availableCryptos = getAllCryptocurrencies();

// Handle investment form submission
$investment_error = '';
$investment_success = false;
$investment_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_investment'])) {
    $selectedCrypto = trim($_POST['selectedCrypto'] ?? '');
    $investmentAmount = floatval($_POST['investmentAmount'] ?? 0);
    $investmentType = trim($_POST['investmentType'] ?? '');
    $confirmInvestment = isset($_POST['confirmInvestment']) ? true : false;
    
    // Validation
    if (!$selectedCrypto || $investmentAmount <= 0) {
        $investment_error = 'Please select a cryptocurrency and enter a valid amount.';
    } elseif (!$investmentType) {
        $investment_error = 'Please select an investment type.';
    } elseif (!$confirmInvestment) {
        $investment_error = 'Please confirm your investment.';
    } else {
        $userEmail = $_SESSION['user_email'];
        
        // Get user's current balance
        $userBalance = getUserBalance($userEmail);
        
        if ($investmentAmount > $userBalance) {
            $investment_error = 'Insufficient balance. Your current balance is $' . number_format($userBalance, 2);
        } else {
            // Get cryptocurrency details from database
            $crypto = getCryptocurrencyBySymbol($selectedCrypto);
            if (!$crypto) {
                $investment_error = 'Invalid cryptocurrency selected.';
            } else {
                $price = floatval($crypto['current_price']);
                $coins = $investmentAmount / $price;
                
                // Add investment to database
                if (addInvestment($userEmail, $selectedCrypto, $investmentAmount, $coins, $price, $investmentType)) {
                    // Update user balance
                    $newBalance = $userBalance - $investmentAmount;
                    if (updateUserBalance($userEmail, $newBalance)) {
                        // Add transaction record
                        addTransaction($userEmail, 'investment', $investmentAmount, $selectedCrypto);
                        
                        $investment_success = true;
                        $investment_message = "Successfully invested $" . number_format($investmentAmount, 2) . " in " . 
                                              htmlspecialchars($crypto['name']) . 
                                              ". You purchased " . number_format($coins, 8) . " coins.";
                    } else {
                        $investment_error = 'Failed to update balance.';
                    }
                } else {
                    $investment_error = 'Failed to create investment record. Please try again.';
                }
            }
        }
    }
}

// Fetch user's current balance and investments
$userEmail = $_SESSION['user_email'] ?? '';
$userBalance = getUserBalance($userEmail);
$totalInvested = getUserTotalInvestments($userEmail);
$userInvestments = [];

$pdo = getDB();
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT balance FROM ch_users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $_SESSION['user_email']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $userBalance = floatval($row['balance'] ?? 0);
        }
    } catch (Exception $e) {
        // Fallback below
    }
}

// Fallback to JSON if DB not available
if ($userBalance === 0) {
    $usersFile = __DIR__ . '/users.json';
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true) ?? [];
        if (isset($users[$_SESSION['user_email']])) {
            $userBalance = floatval($users[$_SESSION['user_email']]['balance'] ?? 0);
        }
    }
}

// Fetch investments - try DB first
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM ch_investments WHERE user_email = :email AND status = :status');
        $stmt->execute([':email' => $_SESSION['user_email'], ':status' => 'active']);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userInvestments[] = [
                'id' => $row['inv_id'],
                'user_email' => $row['user_email'],
                'cryptocurrency' => $row['cryptocurrency'],
                'amount_invested' => floatval($row['amount_invested']),
                'coins_purchased' => floatval($row['coins_purchased']),
                'price_at_purchase' => floatval($row['price_at_purchase']),
                'investment_type' => $row['investment_type'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'maturity_date' => $row['maturity_date']
            ];
            $totalInvested += floatval($row['amount_invested']);
        }
    } catch (Exception $e) {
        // Fallback below
    }
}

// Fetch user investments from database
if ($pdo) {
    try {
        $stmt = $pdo->prepare('
            SELECT inv_id, cryptocurrency, amount_invested, coins_purchased, price_at_purchase, investment_type, status, created_at
            FROM ch_investments 
            WHERE user_email = :email AND status = "active"
            ORDER BY created_at DESC
        ');
        $stmt->execute([':email' => $userEmail]);
        $userInvestments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log('Error fetching investments: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invest - CryptoHub</title>
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
                    <li><a href="dashboard.php<?= $as_admin ?>" class="nav-link">Dashboard</a></li>
                    <li><a href="add-money.php<?= $as_admin ?>" class="nav-link">Add Money</a></li>
                    <li><a href="invest.php<?= $as_admin ?>" class="nav-link active">Invest</a></li>
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
    </nav>

    <div class="invest-container">
        <div class="account-info">
            <h2>Invest in Cryptocurrency</h2>
            <p>Signed in as: <?=htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email'])?></p>
            <div class="info-cards">
                <div class="info-card">
                    <h4>Available Balance</h4>
                    <p id="availableBalance">$<?=number_format($userBalance, 2)?></p>
                </div>
                <div class="info-card">
                    <h4>Total Investment</h4>
                    <p id="totalInvestment">$<?=number_format($totalInvested, 2)?></p>
                </div>
            </div>
        </div>

        <div class="invest-content">
            <div class="crypto-list">
                <h3>Available Cryptocurrencies</h3>
                <div class="crypto-cards" id="cryptoCards">
                    <!-- Crypto cards will be generated by JavaScript -->
                </div>
            </div>

            <div class="investment-form">
                <h3>Make an Investment</h3>
                
                <?php if ($investment_error): ?>
                    <div class="error-box" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                        <?=htmlspecialchars($investment_error)?>
                    </div>
                <?php endif; ?>

                <?php if ($investment_success): ?>
                    <div class="success-box" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                        ✓ <?=htmlspecialchars($investment_message)?>
                    </div>
                <?php endif; ?>
                
                <form id="investForm" method="POST" action="invest.php">
                    <input type="hidden" name="make_investment" value="1">
                    
                    <div class="form-group">
                        <label for="selectedCrypto">Select Cryptocurrency:</label>
                        <select id="selectedCrypto" name="selectedCrypto" required>
                            <option value="">Choose a cryptocurrency</option>
                            <?php foreach ($availableCryptos as $crypto): ?>
                                <option value="<?= htmlspecialchars($crypto['symbol']); ?>">
                                    <?= htmlspecialchars($crypto['name']); ?> (<?= htmlspecialchars($crypto['symbol']); ?>) - $<?= number_format($crypto['current_price'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="error" id="selectedCryptoError"></span>
                    </div>

                    <div class="form-group">
                        <label for="investmentAmount">Investment Amount (USD):</label>
                        <input 
                            type="number" 
                            id="investmentAmount" 
                            name="investmentAmount" 
                            placeholder="Enter amount to invest"
                            step="0.01"
                            required
                        >
                        <span class="error" id="investmentAmountError"></span>
                        <small>Available balance shown above</small>
                    </div>

                    <div class="form-group">
                        <label>Current Price:</label>
                        <p id="currentPrice" class="info-text">Select a cryptocurrency</p>
                    </div>

                    <div class="form-group">
                        <label>Amount of Coins You'll Get:</label>
                        <p id="coinsAmount" class="info-text">0 coins</p>
                    </div>

                    <div class="form-group">
                        <label for="investmentType">Investment Type:</label>
                        <select id="investmentType" name="investmentType" required>
                            <option value="spot">Spot Trading (Immediate)</option>
                            <option value="future">Futures Trading (30 days)</option>
                            <option value="staking">Staking (Annual Returns)</option>
                        </select>
                        <span class="error" id="investmentTypeError"></span>
                    </div>

                    <div id="stakingInfo" class="hidden">
                        <p class="info-box">
                            <strong>Staking:</strong> Lock your coins for 1 year and earn 8-12% annual returns depending on the cryptocurrency.
                        </p>
                    </div>

                    <div class="form-group checkbox">
                        <label>
                            <input type="checkbox" name="confirmInvestment" required> 
                            I confirm I want to invest in this cryptocurrency
                        </label>
                        <span class="error" id="confirmInvestmentError"></span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large">Invest Now</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>

                <div id="investmentSuccess" class="success-message hidden">
                    <h3>Investment Successful!</h3>
                    <p id="investmentSuccessText"></p>
                </div>
            </div>
        </div>

        <div class="portfolio-overview">
            <h3>Your Crypto Holdings</h3>
            <div class="holdings-list" id="holdingsList">
                <?php if (empty($userInvestments)): ?>
                    <p>No holdings yet. Start investing to build your portfolio!</p>
                <?php else: ?>
                    <?php foreach ($userInvestments as $inv): ?>
                        <div class="holding-item">
                            <span><?=htmlspecialchars(ucfirst(str_replace('-', ' ', $inv['cryptocurrency'])))?></span>
                            <span><?=number_format($inv['coins_purchased'], 8)?> coins</span>
                            <span class="holding-value">$<?=number_format($inv['amount_invested'], 2)?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Expose server-side session info and real crypto data to client JS
        window.serverUser = {
            email: <?= json_encode($_SESSION['user_email'] ?? '') ?>,
            name: <?= json_encode($_SESSION['user_name'] ?? '') ?>
        };
        
        // Real-time cryptocurrency data from database
        window.cryptocurrencies = <?= json_encode($availableCryptos); ?>;
        
        // Create cryptoData object from database for compatibility
        const cryptoData = {};
        window.cryptocurrencies.forEach(crypto => {
            cryptoData[crypto['symbol']] = {
                symbol: crypto['symbol'],
                price: parseFloat(crypto['current_price']),
                change: parseFloat(crypto['change_24h']),
                fullName: crypto['name']
            };
        });

        // Generate crypto cards
        function generateCryptoCards() {
            const cryptoCards = document.getElementById('cryptoCards');
            cryptoCards.innerHTML = '';

            window.cryptocurrencies.forEach(crypto => {
                const card = document.createElement('div');
                card.className = 'crypto-card';
                card.innerHTML = `
                    <div class="crypto-header">
                        <h4>${crypto['name']}</h4>
                        <span class="symbol">${crypto['symbol']}</span>
                    </div>
                    <div class="crypto-price">
                        <p class="price">$${parseFloat(crypto['current_price']).toLocaleString()}</p>
                        <p class="change ${crypto['change_24h'] >= 0 ? 'positive' : 'negative'}">
                            ${crypto['change_24h'] >= 0 ? '+' : ''}${crypto['change_24h']}%
                        </p>
                    </div>
                    <button onclick="selectCrypto('${crypto['symbol']}')" class="card-btn">Select</button>
                `;
                cryptoCards.appendChild(card);
            });
        }

        // Select cryptocurrency
        function selectCrypto(cryptoKey) {
            document.getElementById('selectedCrypto').value = cryptoKey;
            updateInvestmentDetails();
        }

        // Update investment details
        document.getElementById('selectedCrypto').addEventListener('change', updateInvestmentDetails);
        document.getElementById('investmentAmount').addEventListener('input', updateInvestmentDetails);
        document.getElementById('investmentType').addEventListener('change', function() {
            const stakingInfo = document.getElementById('stakingInfo');
            if (this.value === 'staking') {
                stakingInfo.classList.remove('hidden');
            } else {
                stakingInfo.classList.add('hidden');
            }
        });

        function updateInvestmentDetails() {
            const selectedCrypto = document.getElementById('selectedCrypto').value;
            const amount = parseFloat(document.getElementById('investmentAmount').value) || 0;

            if (!selectedCrypto) {
                document.getElementById('currentPrice').textContent = 'Select a cryptocurrency';
                document.getElementById('coinsAmount').textContent = '0 coins';
                return;
            }

            const crypto = cryptoData[selectedCrypto];
            if (!crypto) {
                document.getElementById('currentPrice').textContent = 'Select a cryptocurrency';
                document.getElementById('coinsAmount').textContent = '0 coins';
                return;
            }
            
            const coins = amount / crypto.price;
            
            document.getElementById('currentPrice').textContent = `$${crypto.price.toLocaleString()} per ${crypto.symbol}`;
            document.getElementById('coinsAmount').textContent = coins.toFixed(8) + ` ${crypto.symbol}`;
        }

        // Load balance - values are already set from PHP server-side
        // Don't override with localStorage as it can be stale or manipulated
        function loadBalance() {
            // Balance and investment are already displayed from server PHP data
            // No need to load from localStorage
            loadHoldings();
        }

        // Load and display holdings
        function loadHoldings() {
            const holdings = JSON.parse(localStorage.getItem('cryptoHoldings')) || {};
            const holdingsList = document.getElementById('holdingsList');

            if (Object.keys(holdings).length === 0) {
                holdingsList.innerHTML = '<p>No holdings yet. Start investing to build your portfolio!</p>';
                return;
            }

            holdingsList.innerHTML = '';
            for (const [cryptoKey, amount] of Object.entries(holdings)) {
                const crypto = cryptoData[cryptoKey];
                const value = amount * crypto.price;
                const holding = document.createElement('div');
                holding.className = 'holding-item';
                holding.innerHTML = `
                    <span>${crypto.fullName} (${crypto.symbol})</span>
                    <span>${amount.toFixed(8)} coins</span>
                    <span class="holding-value">$${value.toFixed(2)}</span>
                `;
                holdingsList.appendChild(holding);
            }
        }

        // Form submission
        document.getElementById('investForm').addEventListener('submit', (e) => {
            // Only validate, don't prevent default - let form submit to server
            if (!validateInvestForm()) {
                e.preventDefault();
                return;
            }
            
            // If validation passes, allow form to submit to server normally
            // Server will handle the investment and database operations
        });

        window.addEventListener('load', () => {
            generateCryptoCards();
            loadBalance();
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
