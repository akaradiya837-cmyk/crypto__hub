<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_helpers.php';

require_admin_login();

$message = '';
$message_type = '';

// Handle adding new cryptocurrency
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = trim($_POST['action'] ?? '');
    
    if ($action === 'add_crypto') {
        $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $current_price = floatval($_POST['current_price'] ?? 0);
        $change_24h = floatval($_POST['change_24h'] ?? 0);
        $change_7d = floatval($_POST['change_7d'] ?? 0);
        
        // Validation
        if (!$symbol || !$name || $current_price <= 0) {
            $message = 'Please fill all required fields with valid data.';
            $message_type = 'error';
        } else if (strlen($symbol) > 10 || strlen($name) > 100) {
            $message = 'Symbol must be less than 10 characters, Name less than 100.';
            $message_type = 'error';
        } else {
            $pdo = getDB();
            if (!$pdo) {
                $message = 'Database connection failed. Please try again.';
                $message_type = 'error';
            } else {
                try {
                    // Check if cryptocurrency already exists
                    $check = $pdo->prepare('SELECT id FROM ch_cryptocurrencies WHERE symbol = :symbol LIMIT 1');
                    $check->execute([':symbol' => $symbol]);
                    if ($check->fetch()) {
                        $message = 'Cryptocurrency with this symbol already exists!';
                        $message_type = 'error';
                    } else {
                        // Insert new cryptocurrency
                        $stmt = $pdo->prepare('
                            INSERT INTO ch_cryptocurrencies (symbol, name, current_price, change_24h, change_7d, status)
                            VALUES (:symbol, :name, :price, :change_24h, :change_7d, :status)
                        ');
                        
                        if ($stmt->execute([
                            ':symbol' => $symbol,
                            ':name' => $name,
                            ':price' => $current_price,
                            ':change_24h' => $change_24h,
                            ':change_7d' => $change_7d,
                            ':status' => 'active'
                        ])) {
                            $message = '✓ Cryptocurrency added successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to add cryptocurrency. Please try again.';
                            $message_type = 'error';
                        }
                    }
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
    } elseif ($action === 'update_crypto') {
        $id = intval($_POST['crypto_id'] ?? 0);
        $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $current_price = floatval($_POST['current_price'] ?? 0);
        $change_24h = floatval($_POST['change_24h'] ?? 0);
        $change_7d = floatval($_POST['change_7d'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');
        
        if (!$id || !$symbol || !$name || $current_price <= 0) {
            $message = 'Please fill all required fields.';
            $message_type = 'error';
        } else {
            $pdo = getDB();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare('
                        UPDATE ch_cryptocurrencies 
                        SET symbol = :symbol, name = :name, current_price = :price, 
                            change_24h = :change_24h, change_7d = :change_7d, status = :status
                        WHERE id = :id
                    ');
                    
                    if ($stmt->execute([
                        ':id' => $id,
                        ':symbol' => $symbol,
                        ':name' => $name,
                        ':price' => $current_price,
                        ':change_24h' => $change_24h,
                        ':change_7d' => $change_7d,
                        ':status' => $status
                    ])) {
                        $message = '✓ Cryptocurrency updated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to update cryptocurrency.';
                        $message_type = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
    } elseif ($action === 'delete_crypto') {
        $id = intval($_POST['crypto_id'] ?? 0);
        
        if (!$id) {
            $message = 'Invalid cryptocurrency ID.';
            $message_type = 'error';
        } else {
            $pdo = getDB();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare('DELETE FROM ch_cryptocurrencies WHERE id = :id');
                    if ($stmt->execute([':id' => $id])) {
                        $message = '✓ Cryptocurrency deleted successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to delete cryptocurrency.';
                        $message_type = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
    }
}

// Get all cryptocurrencies
$cryptocurrencies = [];
$pdo = getDB();
if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM ch_cryptocurrencies ORDER BY symbol ASC');
        $cryptocurrencies = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        error_log('Error fetching cryptocurrencies: ' . $e->getMessage());
    }
}

// Resolve admin identity
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
    <title>Manage Cryptocurrencies - Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .crypto-form { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .crypto-form h3 { margin-top: 0; color: #333; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .crypto-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .crypto-table th { background: #667eea; color: white; padding: 12px; text-align: left; }
        .crypto-table td { padding: 12px; border-bottom: 1px solid #ddd; }
        .crypto-table tr:hover { background: #f5f5f5; }
        .btn-edit { background: #4CAF50; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-delete { background: #f44336; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px; }
        .status-active { color: #4CAF50; font-weight: bold; }
        .status-inactive { color: #999; font-weight: bold; }
    </style>
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
                <li><a href="admin-cryptocurrencies.php" class="nav-link active">Cryptocurrencies</a></li>
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
                <li><a href="admin-panel.php" class="sidebar-link">Dashboard</a></li>
                <li><a href="admin-users.php" class="sidebar-link">Manage Users</a></li>
                <li><a href="admin-transactions.php" class="sidebar-link">💳 Transactions</a></li>
                <li><a href="admin-reports.php" class="sidebar-link">📈 Reports</a></li>
                <li><a href="admin-cryptocurrencies.php" class="sidebar-link active">🪙 Cryptocurrencies</a></li>
                <li><a href="admin-settings.php" class="sidebar-link">⚙️ Settings</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>🪙 Manage Cryptocurrencies</h1>
                <p>Add, update, or delete cryptocurrencies available on the platform</p>
            </div>

            <?php if ($message): ?>
                <div style="padding: 15px; border-radius: 5px; margin-bottom: 20px; background: <?= $message_type === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $message_type === 'success' ? '#155724' : '#721c24' ?>; border: 1px solid <?= $message_type === 'success' ? '#c3e6cb' : '#f5c6cb' ?>;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Add New Cryptocurrency Form -->
            <div class="crypto-form">
                <h3>➕ Add New Cryptocurrency</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_crypto">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="symbol">Symbol (e.g., BTC):</label>
                            <input type="text" id="symbol" name="symbol" placeholder="BTC" required maxlength="10" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div class="form-group">
                            <label for="name">Name (e.g., Bitcoin):</label>
                            <input type="text" id="name" name="name" placeholder="Bitcoin" required maxlength="100" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_price">Current Price (USD):</label>
                            <input type="number" id="current_price" name="current_price" placeholder="42500.00" step="0.01" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div class="form-group">
                            <label for="change_24h">24h Change (%):</label>
                            <input type="number" id="change_24h" name="change_24h" placeholder="2.5" step="0.1" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="change_7d">7d Change (%):</label>
                            <input type="number" id="change_7d" name="change_7d" placeholder="5.2" step="0.1" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; margin-top: 25px;">Add Cryptocurrency</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Cryptocurrencies List -->
            <div style="background: white; padding: 20px; border-radius: 8px;">
                <h3>📊 Cryptocurrencies List (<?= count($cryptocurrencies) ?>)</h3>
                
                <?php if (empty($cryptocurrencies)): ?>
                    <p style="color: #999; padding: 20px;">No cryptocurrencies found. Add one using the form above.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="crypto-table">
                            <thead>
                                <tr>
                                    <th>Symbol</th>
                                    <th>Name</th>
                                    <th>Current Price</th>
                                    <th>24h Change</th>
                                    <th>7d Change</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cryptocurrencies as $crypto): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($crypto['symbol']) ?></strong></td>
                                        <td><?= htmlspecialchars($crypto['name']) ?></td>
                                        <td>$<?= number_format($crypto['current_price'], 2) ?></td>
                                        <td style="color: <?= $crypto['change_24h'] >= 0 ? '#4CAF50' : '#f44336' ?>;">
                                            <?= $crypto['change_24h'] >= 0 ? '+' : '' ?><?= number_format($crypto['change_24h'], 2) ?>%
                                        </td>
                                        <td style="color: <?= $crypto['change_7d'] >= 0 ? '#4CAF50' : '#f44336' ?>;">
                                            <?= $crypto['change_7d'] >= 0 ? '+' : '' ?><?= number_format($crypto['change_7d'], 2) ?>%
                                        </td>
                                        <td>
                                            <span class="status-<?= $crypto['status'] ?>">
                                                <?= ucfirst($crypto['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= isset($crypto['created_at']) && $crypto['created_at'] ? date('Y-m-d', strtotime($crypto['created_at'])) : 'N/A' ?></td>
                                        <td>
                                            <button class="btn-edit" onclick="editCrypto(<?= $crypto['id'] ?>, '<?= htmlspecialchars($crypto['symbol']) ?>', '<?= htmlspecialchars($crypto['name']) ?>', <?= $crypto['current_price'] ?>, <?= $crypto['change_24h'] ?>, <?= $crypto['change_7d'] ?>, '<?= $crypto['status'] ?>')">Edit</button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this cryptocurrency?');">
                                                <input type="hidden" name="action" value="delete_crypto">
                                                <input type="hidden" name="crypto_id" value="<?= $crypto['id'] ?>">
                                                <button type="submit" class="btn-delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal (hidden by default) -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto;">
            <h3>Edit Cryptocurrency</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_crypto">
                <input type="hidden" id="editCryptoId" name="crypto_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Symbol:</label>
                    <input type="text" id="editSymbol" name="symbol" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Name:</label>
                    <input type="text" id="editName" name="name" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Current Price:</label>
                    <input type="number" id="editPrice" name="current_price" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>24h Change (%):</label>
                    <input type="number" id="edit24h" name="change_24h" step="0.1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>7d Change (%):</label>
                    <input type="number" id="edit7d" name="change_7d" step="0.1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Status:</label>
                    <select id="editStatus" name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Update Cryptocurrency</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editCrypto(id, symbol, name, price, change24h, change7d, status) {
            document.getElementById('editCryptoId').value = id;
            document.getElementById('editSymbol').value = symbol;
            document.getElementById('editName').value = name;
            document.getElementById('editPrice').value = price;
            document.getElementById('edit24h').value = change24h;
            document.getElementById('edit7d').value = change7d;
            document.getElementById('editStatus').value = status;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
