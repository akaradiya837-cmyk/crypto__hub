<?php
session_start();
$is_logged_in = isset($_SESSION['user_email']) || (!empty($_GET['as_admin']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']);
$as_admin = !empty($_GET['as_admin']) ? '?as_admin=1' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoHub - Your Crypto Journey Starts Here</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Custom styles just for the home page hero section to make it pop */
        .hero-section {
            text-align: center;
            color: white;
            padding: 100px 20px;
            /* Using your existing theme gradient */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .hero-section h1 { 
            font-size: 56px; 
            margin-bottom: 20px; 
            font-weight: 800;
        }
        .hero-section p { 
            font-size: 22px; 
            margin-bottom: 40px; 
            opacity: 0.9; 
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .hero-buttons { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
        }
        .btn-white {
            background: white;
            color: #667eea;
            font-weight: 700;
        }
        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
            font-weight: 700;
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        /* Features Grid */
        .features-section { 
            max-width: 1200px; 
            margin: -40px auto 50px auto; /* Pulls cards slightly up over the gradient */
            padding: 0 20px; 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 30px; 
        }
        .feature-card { 
            background: white; 
            padding: 40px 30px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); 
            text-align: center; 
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-card h3 { 
            color: #333; 
            margin: 15px 0; 
            font-size: 24px; 
        }
        .feature-card p { 
            color: #666; 
            line-height: 1.6; 
            font-size: 15px;
        }
        .feature-icon {
            font-size: 40px;
        }
        
        /* Footer */
        .home-footer {
            background: #f8f9fa;
            color: #333;
            padding: 50px 20px 20px;
            margin-top: 60px;
            border-top: 1px solid #e0e0e0;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }

        .footer-section h3 {
            color: #667eea;
            font-size: 16px;
            margin-bottom: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer-section p {
            color: #666;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 10px;
        }

        .footer-section a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: #667eea;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .footer-contact {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            color: #666;
            font-size: 14px;
        }

        .footer-socials {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s;
        }

        .social-icon:hover {
            background: #764ba2;
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
            text-align: center;
            color: #666;
            font-size: 13px;
        }

        .footer-bottom p {
            margin: 8px 0;
        }

        .footer-bottom a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-bottom a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar" style="box-shadow: none;">
        <div class="nav-container">
            <a href="home.php<?= $as_admin ?>" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <?php if ($is_logged_in): ?>
                    <li><a href="home.php<?= $as_admin ?>" class="nav-link active">Home</a></li>
                    <li><a href="dashboard.php<?= $as_admin ?>" class="nav-link">Dashboard</a></li>
                    <li><a href="add-money.php<?= $as_admin ?>" class="nav-link">Add Money</a></li>
                    <li><a href="invest.php<?= $as_admin ?>" class="nav-link">Invest</a></li>
                    <li><a href="logout.php" class="nav-link" id="logoutBtn">Logout</a></li>
                <?php else: ?>
                    <li><a href="home.php" class="nav-link active">Home</a></li>
                    <li><a href="index.php" class="nav-link">Login</a></li>
                    <li><a href="register.php" class="nav-link">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="hero-section">
        <h1>Welcome to CryptoHub</h1>
        <p>The most secure, intuitive, and lightning-fast platform to buy, sell, and manage your cryptocurrency portfolio. Start building your financial future today.</p>
        <?php if (!$is_logged_in): ?>
        <div class="hero-buttons">
            <a href="register.php" class="btn btn-white" style="width: auto; padding: 15px 40px;">Get Started Now</a>
            <a href="index.php" class="btn btn-outline" style="width: auto; padding: 15px 40px;">User Login</a>
        </div>
        <?php endif; ?>
    </div>

    <div class="features-section">
        <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h3>Bank-Grade Security</h3>
            <p>Your digital assets are protected by industry-leading encryption and secure storage solutions. Trade with absolute peace of mind knowing your funds are safe.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⚡</div>
            <h3>Instant Transactions</h3>
            <p>Execute spot and futures trades instantly with our high-performance matching engine. Never miss a critical market movement again.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Real-Time Analytics</h3>
            <p>Track your portfolio performance with advanced Chart.js integrations and live market data across all major cryptocurrencies.</p>
        </div>
    </div>


    <!-- Crypto Prices Section -->
    <section class="crypto-prices-section" style="max-width:1200px;margin:40px auto 0 auto;padding:0 20px;">
        <h2 style="text-align:center;color:#667eea;margin-bottom:24px;">Live Cryptocurrency Prices</h2>
        <!-- Vertical scrolling ticker -->
        <div class="crypto-ticker-wrap" style="max-width:760px;margin:0 auto;">
            <div id="cryptoPrices" class="crypto-ticker-track" style="">
            </div>
        </div>
    </section>

    <!-- Crypto Price Trend Graph -->
    <section style="max-width:900px;margin:40px auto 0 auto;padding:0 20px;">
        <h2 style="text-align:center;color:#667eea;margin-bottom:24px;">Crypto Price Comparison</h2>
        <canvas id="homePriceChart" height="90"></canvas>
    </section>

    <footer class="home-footer">
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
                        <li><a href="contact-us.php">Contact Us</a></li>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Example static prices (replace with API fetch for real data)
    const cryptoData = [
        { name: 'Bitcoin', symbol: 'BTC', price: 42000, change: 2.1, color: '#F7931A' },
        { name: 'Ethereum', symbol: 'ETH', price: 2500, change: -1.3, color: '#627EEA' },
        { name: 'Cardano', symbol: 'ADA', price: 0.98, change: 0.5, color: '#0033FE' },
        { name: 'Solana', symbol: 'SOL', price: 120, change: 3.2, color: '#00D4AA' },
        { name: 'Ripple', symbol: 'XRP', price: 2.15, change: 0.8, color: '#23F7DD' },
        { name: 'Litecoin', symbol: 'LTC', price: 800, change: -0.7, color: '#EACB6B' },
        { name: 'Dogecoin', symbol: 'DOGE', price: 0.15, change: 4.5, color: '#C2A633' },
        { name: 'Polkadot', symbol: 'DOT', price: 7.25, change: 1.9, color: '#E6007A' }
    ];

    function renderCryptoPrices() {
        const container = document.getElementById('cryptoPrices');
        if (!container) return;

        // Render as horizontal list items for ticker
        const singleHTML = cryptoData.map(coin => `
            <div class="crypto-item" style="background:white;border-radius:14px;box-shadow:0 6px 24px rgba(102,126,234,0.09);padding:26px 28px;text-align:left;display:flex;align-items:center;gap:18px;min-width:620px;">
                <div class="symbol" style="width:96px;height:96px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:28px;color:${coin.color};background:rgba(0,0,0,0.04);">${coin.symbol}</div>
                <div style="flex:1">
                    <div style="font-size:20px;font-weight:800;color:#333;">${coin.name}</div>
                    <div style="font-size:22px;font-weight:900;margin-top:6px;color:#667eea;">$${coin.price.toLocaleString()}</div>
                </div>
                <div style="font-size:18px;font-weight:800;color:${coin.change>=0?'#27ae60':'#e74c3c'};">${coin.change>=0?'+':''}${coin.change}%</div>
            </div>
        `).join('');

        // Put two copies side-by-side for looping
        container.innerHTML = singleHTML + singleHTML;

        // Force layout then compute pixel scroll amount equal to one set's width
        const totalWidth = container.scrollWidth;
        const singleWidth = totalWidth / 2;

        // Apply CSS animation duration based on number of unique items
        const itemCount = cryptoData.length;
        // base 6 seconds per item for horizontal scroll
        // Set speedFactor so overall animation is ~60% faster than original baseline.
        // New speedFactor = 1.0 gives duration = itemCount * 6 (approx 60% faster than original 2.5 factor baseline).
        const speedFactor = 1.0;
        const duration = Math.max(6, Math.round(itemCount * 6 * speedFactor));

        // Create/update dynamic keyframes to translate by the singleWidth in px
        const styleId = 'crypto-ticker-dynamic-styles';
        let dyn = document.getElementById(styleId);
        const keyframes = `@keyframes scrollLeft{0%{transform:translateX(0);}100%{transform:translateX(-${singleWidth}px);}}`;
        const css = `
        .crypto-ticker-wrap{overflow:hidden;height:300px;}
        .crypto-ticker-track{display:flex;flex-direction:row;gap:22px;padding:18px 12px;align-items:center}
        .crypto-ticker-track .crypto-item{background:white;min-width:620px;padding:26px 28px;border-radius:14px}
        .crypto-ticker-track .crypto-item div{line-height:1}
        .crypto-ticker-track .crypto-item .symbol{width:96px;height:96px;border-radius:12px;font-size:28px}
        ${keyframes}
        `;
        if (dyn) dyn.textContent = css; else { dyn = document.createElement('style'); dyn.id = styleId; dyn.appendChild(document.createTextNode(css)); document.head.appendChild(dyn); }

        container.style.animation = `scrollLeft ${duration}s linear infinite`;
    }

    // Inject minimal CSS for ticker (kept inline to avoid editing styles.css)
    (function injectTickerStyles(){
        const css = `
        .crypto-ticker-wrap{overflow:hidden;height:300px;}
        .crypto-ticker-track{display:flex;flex-direction:row;gap:22px;padding:18px 12px;align-items:center}
        .crypto-ticker-track .crypto-item{background:white;min-width:620px;padding:26px 28px;border-radius:14px}
        .crypto-ticker-track .crypto-item div{line-height:1}
        .crypto-ticker-track .crypto-item .symbol{width:96px;height:96px;border-radius:12px;font-size:28px}
        @keyframes scrollLeft{0%{transform:translateX(0);}100%{transform:translateX(-50%);}}
        `;
        const s = document.createElement('style'); s.appendChild(document.createTextNode(css)); document.head.appendChild(s);
    })();

    document.addEventListener('DOMContentLoaded', function() {
        renderCryptoPrices();
        // Render bar chart for all crypto prices
        const chartCanvas = document.getElementById('homePriceChart');
        if (chartCanvas) {
            const barCtx = chartCanvas.getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: cryptoData.map(c => c.name + ' (' + c.symbol + ')'),
                    datasets: [{
                        label: 'Current Price (USD)',
                        data: cryptoData.map(c => c.price),
                        backgroundColor: [
                            'rgba(247,147,26,0.25)',   // BTC
                            'rgba(98,126,234,0.25)',   // ETH
                            'rgba(0,51,254,0.25)',     // ADA
                            'rgba(0,212,170,0.25)',    // SOL
                            'rgba(35,247,221,0.25)',   // XRP
                            'rgba(234,203,107,0.25)',  // LTC
                            'rgba(194,166,51,0.25)',   // DOGE
                            'rgba(230,0,122,0.25)'     // DOT
                        ],
                        borderColor: [
                            '#F7931A', '#627EEA', '#0033FE', '#00D4AA', '#23F7DD', '#EACB6B', '#C2A633', '#E6007A'
                        ],
                        borderWidth: 2,
                        borderRadius: 8,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
    </script>

</body>
</html>