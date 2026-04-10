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

// Handle form submission
$transaction_error = '';
$transaction_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_money'])) {
    $paymentMethod = trim($_POST['paymentMethod'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $cardName = trim($_POST['cardName'] ?? '');
    $cardNumber = trim($_POST['cardNumber'] ?? '');
    $cardExpiry = trim($_POST['cardExpiry'] ?? '');
    $cardCVV = trim($_POST['cardCVV'] ?? '');
    $bankName = trim($_POST['bankName'] ?? '');
    $accountNumber = trim($_POST['accountNumber'] ?? '');
    $routingNumber = trim($_POST['routingNumber'] ?? '');
    $confirm = isset($_POST['confirm']) ? true : false;
    
    // Validation
    if (!$paymentMethod || $amount <= 0) {
        $transaction_error = 'Please select a payment method and enter a valid amount.';
    } elseif ($amount < 10 || $amount > 100000) {
        $transaction_error = 'Amount must be between $10 and $100,000.';
    } elseif (!$confirm) {
        $transaction_error = 'Please confirm that the information is correct.';
    } else {
        $userEmail = $_SESSION['user_email'];
        
        // Validate payment details based on method
        if (($paymentMethod === 'credit-card' || $paymentMethod === 'debit-card') && 
            (empty($cardName) || empty($cardNumber) || empty($cardExpiry) || empty($cardCVV))) {
            $transaction_error = 'Please fill in all card details.';
        } elseif ($paymentMethod === 'bank-transfer' && 
                 (empty($bankName) || empty($accountNumber) || empty($routingNumber))) {
            $transaction_error = 'Please fill in all bank details.';
        } else {
            // Create transaction record
            $lastDigits = ($paymentMethod === 'credit-card' || $paymentMethod === 'debit-card') 
                        ? substr(str_replace(' ', '', $cardNumber), -4) 
                        : substr($accountNumber, -4);
            
            // Add transaction to database
            if (addTransaction($userEmail, 'deposit', $amount, 'USD', null)) {
                // Update user balance
                $currentBalance = getUserBalance($userEmail);
                $newBalance = $currentBalance + $amount;
                
                if (updateUserBalance($userEmail, $newBalance)) {
                    $transaction_success = true;
                    // Redirect to dashboard after 3 seconds
                    header('Refresh: 3; url=dashboard.php');
                } else {
                    $transaction_error = 'Failed to update balance. Please contact support.';
                }
            } else {
                $transaction_error = 'Failed to process transaction. Please try again.';
            }
        }
    }
}

// Get user's current balance
$userEmail = $_SESSION['user_email'] ?? '';
$userBalance = getUserBalance($userEmail);
$recentTransactions = getUserTransactions($userEmail, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money - CryptoHub</title>
    <link rel="stylesheet" href="styles.css">
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
                    <li><a href="add-money.php<?= $as_admin ?>" class="nav-link active">Add Money</a></li>
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

    <div class="main-container">
        <div class="form-container">
            <h2>Add Money to Your Account</h2>
            <p>Signed in as: <?=htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email'])?></p>
            
            <div class="balance-display">
                <h3>Current Balance: <span id="currentBalance">$<?=number_format($userBalance, 2)?></span></h3>
            </div>

            <?php if ($transaction_error): ?>
                <div class="error-box" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                    <?=htmlspecialchars($transaction_error)?>
                </div>
            <?php endif; ?>

            <?php if ($transaction_success): ?>
                <div class="success-box" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                    ✓ Transaction completed successfully! Your account has been credited.
                </div>
            <?php endif; ?>

            <form id="addMoneyForm" class="add-money-form" method="POST" action="add-money.php">
                <input type="hidden" name="add_money" value="1">
                
                <div class="form-group">
                    <label for="paymentMethod">Payment Method:</label>
                    <select id="paymentMethod" name="paymentMethod" required>
                        <option value="">Select Payment Method</option>
                        <option value="credit-card">Credit Card</option>
                        <option value="debit-card">Debit Card</option>
                        <option value="bank-transfer">Bank Transfer</option>
                        <option value="paypal">PayPal</option>
                        <option value="crypto">Cryptocurrency</option>
                    </select>
                    <span class="error" id="paymentMethodError"></span>
                </div>

                <div class="form-group">
                    <label for="amount">Amount (USD):</label>
                    <input 
                        type="number" 
                        id="amount" 
                        name="amount" 
                        placeholder="Enter amount"
                        step="0.01"
                        required
                    >
                    <span class="error" id="amountError"></span>
                    <small>Minimum: $10 | Maximum: $100,000</small>
                </div>

                <div id="cardDetails" class="hidden">
                    <div class="form-group">
                        <label for="cardName">Cardholder Name:</label>
                        <input 
                            type="text" 
                            id="cardName" 
                            name="cardName" 
                            placeholder="Name on card"
                        >
                        <span class="error" id="cardNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="cardNumber">Card Number:</label>
                        <input 
                            type="text" 
                            id="cardNumber" 
                            name="cardNumber" 
                            placeholder="1234 5678 9012 3456"
                            maxlength="19"
                        >
                        <span class="error" id="cardNumberError"></span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cardExpiry">Expiry Date:</label>
                            <input 
                                type="text" 
                                id="cardExpiry" 
                                name="cardExpiry" 
                                placeholder="MM/YY"
                                maxlength="5"
                            >
                            <span class="error" id="cardExpiryError"></span>
                        </div>

                        <div class="form-group">
                            <label for="cardCVV">CVV:</label>
                            <input 
                                type="text" 
                                id="cardCVV" 
                                name="cardCVV" 
                                placeholder="123"
                                maxlength="4"
                            >
                            <span class="error" id="cardCVVError"></span>
                        </div>
                    </div>
                </div>

                <div id="bankDetails" class="hidden">
                    <div class="form-group">
                        <label for="bankName">Bank Name:</label>
                        <input 
                            type="text" 
                            id="bankName" 
                            name="bankName" 
                            placeholder="Enter bank name"
                        >
                        <span class="error" id="bankNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="accountNumber">Account Number:</label>
                        <input 
                            type="text" 
                            id="accountNumber" 
                            name="accountNumber" 
                            placeholder="Enter account number"
                        >
                        <span class="error" id="accountNumberError"></span>
                    </div>

                    <div class="form-group">
                        <label for="routingNumber">Routing Number:</label>
                        <input 
                            type="text" 
                            id="routingNumber" 
                            name="routingNumber" 
                            placeholder="Enter routing number"
                        >
                        <span class="error" id="routingNumberError"></span>
                    </div>
                </div>

                <div class="form-group checkbox">
                    <label>
                        <input type="checkbox" name="confirm" required> 
                        I confirm this is correct
                    </label>
                    <span class="error" id="confirmError"></span>
                </div>

                <button type="submit" class="btn btn-primary">Add Money</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>

            <div id="successMessage" class="success-message hidden">
                <h3>Success!</h3>
                <p id="successText"></p>
            </div>
        </div>

        <div class="info-box">
            <h3>Payment Information</h3>
            <ul>
                <li>✓ Secure and encrypted transactions</li>
                <li>✓ Multiple payment methods available</li>
                <li>✓ Instant processing for most payments</li>
                <li>✓ No hidden fees</li>
                <li>✓ 24/7 customer support</li>
            </ul>
        </div>
    </div>

    <script>
        // Expose server-side session info to client JS
        window.serverUser = {
            email: <?= json_encode($_SESSION['user_email'] ?? '') ?>,
            name: <?= json_encode($_SESSION['user_name'] ?? '') ?>
        };
    </script>
    <script src="script.js"></script>
    <script>
        const form = document.getElementById('addMoneyForm');
        const paymentMethod = document.getElementById('paymentMethod');
        const cardDetails = document.getElementById('cardDetails');
        const bankDetails = document.getElementById('bankDetails');

        // Show/hide payment details based on method
        paymentMethod.addEventListener('change', () => {
            cardDetails.classList.add('hidden');
            bankDetails.classList.add('hidden');

            if (paymentMethod.value === 'credit-card' || paymentMethod.value === 'debit-card') {
                cardDetails.classList.remove('hidden');
            } else if (paymentMethod.value === 'bank-transfer') {
                bankDetails.classList.remove('hidden');
            }
        });

        // Load current balance
        function loadBalance() {
            const balance = parseFloat(localStorage.getItem('userBalance')) || 0;
            document.getElementById('currentBalance').textContent = '$' + balance.toFixed(2);
        }

        // Form submission - validate and allow server to handle transaction
        form.addEventListener('submit', (e) => {
            if (!validateAddMoneyForm()) {
                e.preventDefault();
                return;
            }
            // Allow form to submit to server - PHP will process transaction and update database
        });

        // Validate card number
        document.getElementById('cardNumber').addEventListener('input', function() {
            let value = this.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            this.value = formattedValue;
        });

        // Validate expiry date
        document.getElementById('cardExpiry').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            this.value = value;
        });

        // Validate CVV (numeric only)
        document.getElementById('cardCVV').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        window.addEventListener('load', loadBalance);
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
