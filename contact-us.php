<?php
require_once __DIR__ . '/auth.php';
// Require login to access this page
require_user_or_admin_view();

// If admin is viewing user mode, set a JS flag
if (is_admin() && !isset($_SESSION['user_email']) && !empty($_GET['as_admin']) && $_GET['as_admin'] == '1') {
    $_SESSION['user_email'] = 'admin@crypto.com';
    $_SESSION['user_name'] = 'Admin UserMode';
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message_text)) {
        $message = 'Please fill in all fields.';
        $message_type = 'error';
    } else {
        // Save message to database first, then fallback to JSON
        $contact_data = [
            'user_email' => $_SESSION['user_email'],
            'user_name' => $_SESSION['user_name'],
            'subject' => $subject,
            'message' => $message_text,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ];
        
        // Try to save to DB first
        $dbSuccess = false;
        $pdo = getDB();
        if ($pdo) {
            try {
                $ins = $pdo->prepare('INSERT INTO ch_messages (user_email, user_name, subject, message, ip_address, status, created_at) VALUES (:user_email, :user_name, :subject, :message, :ip_address, :status, :created_at)');
                $ins->execute([
                    ':user_email' => $contact_data['user_email'],
                    ':user_name' => $contact_data['user_name'],
                    ':subject' => $contact_data['subject'],
                    ':message' => $contact_data['message'],
                    ':ip_address' => $contact_data['ip_address'],
                    ':status' => 'unread',
                    ':created_at' => $contact_data['timestamp']
                ]);
                $dbSuccess = true;
            } catch (Exception $e) {
                // Continue to JSON fallback
            }
        }
        
        // Also save to JSON for fallback
        $contacts_dir = __DIR__ . '/contacts';
        if (!is_dir($contacts_dir)) {
            mkdir($contacts_dir, 0755, true);
        }
        
        $contacts_file = $contacts_dir . '/messages.json';
        $messages = [];
        
        if (file_exists($contacts_file)) {
            $messages = json_decode(file_get_contents($contacts_file), true) ?? [];
        }
        
        $messages[] = $contact_data;
        @file_put_contents($contacts_file, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $message = 'Thank you! Your message has been sent successfully. We\'ll get back to you soon.';
        $message_type = 'success';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - CryptoHub</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .contact-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .contact-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .contact-header h1 {
            color: #667eea;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .contact-header p {
            color: #666;
            font-size: 16px;
        }

        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 40px;
            padding-top: 40px;
            border-top: 1px solid #e0e0e0;
        }

        .info-card {
            text-align: center;
            padding: 20px;
        }

        .info-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .info-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-logo"><img src="image/logo.png.png" alt="CryptoHub Logo"></a>
            <ul class="nav-menu">
                <?php $as_admin = !empty($_GET['as_admin']) ? '?as_admin=1' : ''; ?>
                <li><a href="home.php<?= $as_admin ?>" class="nav-link">Home</a></li>
                <li><a href="dashboard.php<?= $as_admin ?>" class="nav-link">Dashboard</a></li>
                <li><a href="add-money.php<?= $as_admin ?>" class="nav-link">Add Money</a></li>
                <li><a href="invest.php<?= $as_admin ?>" class="nav-link">Invest</a></li>
                <li><a href="contact-us.php<?= $as_admin ?>" class="nav-link active">Contact Us</a></li>
                <li><a href="profile.php<?= $as_admin ?>" class="nav-link">Profile</a></li>
                <?php if (is_admin() && empty($_GET['as_admin'])): ?>
                    <li><a href="admin-panel.php" class="nav-link" style="color: #FFD700;">🔐 Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="nav-link" id="logoutBtn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="contact-container">
        <div class="contact-header">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="contact-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_SESSION['user_email']) ?>" readonly>
                <small>Your registered email address</small>
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" readonly>
                <small>Your registered name</small>
            </div>

            <div class="form-group">
                <label for="subject">Subject *</label>
                <input type="text" id="subject" name="subject" placeholder="Enter the subject of your message">
            </div>

            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" placeholder="Write your message here... (minimum 10 characters)"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-large">Send Message</button>
        </form>

        <div class="contact-info">
            <div class="info-card">
                <div class="info-icon">📧</div>
                <h3>Email</h3>
                <p>support@cryptohub.com</p>
            </div>

            <div class="info-card">
                <div class="info-icon">💬</div>
                <h3>Live Chat</h3>
                <p>Available 24/7 on our website</p>
            </div>

            <div class="info-card">
                <div class="info-icon">⏰</div>
                <h3>Response Time</h3>
                <p>We typically respond within 24 hours</p>
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

    <script src="script.js"></script>
</body>
</html>
