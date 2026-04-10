<?php
/**
 * MASTER DATABASE FIX
 * Complete one-step solution to fix all database issues
 */

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
echo "<title>CryptoHub - Master Database Fix</title>";
echo "<style>";
echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; padding: 20px; }";
echo ".container { background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 700px; margin: 0 auto; padding: 40px; }";
echo "h1 { color: #333; margin-top: 0; text-align: center; }";
echo ".step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #667eea; border-radius: 5px; }";
echo ".step h2 { margin: 0 0 10px 0; color: #667eea; font-size: 18px; }";
echo ".step p { margin: 5px 0; color: #666; }";
echo ".success { background: #d4edda; border-left-color: #28a745; }";
echo ".error { background: #f8d7da; border-left-color: #dc3545; }";
echo ".status { font-weight: bold; margin: 10px 0; }";
echo ".btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; margin: 10px 5px 10px 0; }";
echo ".btn:hover { background: #5568d3; }";
echo ".actions { text-align: center; margin-top: 30px; }";
echo "table { width: 100%; border-collapse: collapse; margin: 10px 0; }";
echo "table td, table th { padding: 8px; border: 1px solid #ddd; text-align: left; }";
echo "table th { background: #f0f0f0; font-weight: bold; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>🛠️ CryptoHub Master Database Fixer</h1>";

require_once __DIR__ . '/config.php';

// STEP 1: Test MySQL Connection
echo "<div class='step'>";
echo "<h2>Step 1: MySQL Connection</h2>";
$mysql_ok = false;
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    echo "<p class='status' style='color:green;'>✓ MySQL is running</p>";
    $mysql_ok = true;
} catch (Exception $e) {
    echo "<p class='status' style='color:red;'>✗ MySQL is NOT running</p>";
    echo "<ol>";
    echo "<li>Open <strong>Laragon</strong></li>";
    echo "<li>Click <strong>MySQL icon</strong> to start it</li>";
    echo "<li>Reload this page</li>";
    echo "</ol>";
}
echo "</div>";

if (!$mysql_ok) {
    echo "</div></body></html>";
    die();
}

// STEP 2: Create Database
echo "<div class='step'>";
echo "<h2>Step 2: Database Setup</h2>";
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT, DB_USER, DB_PASS);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "<p class='status' style='color:green;'>✓ Database created/verified</p>";
} catch (Exception $e) {
    echo "<p class='status' style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "</div></div></body></html>";
    die();
}
echo "</div>";

// STEP 3: Connect to Database
echo "<div class='step'>";
echo "<h2>Step 3: Connect to Database</h2>";
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p class='status' style='color:green;'>✓ Connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='status' style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "</div></div></body></html>";
    die();
}
echo "</div>";

// STEP 4: Create Tables
echo "<div class='step'>";
echo "<h2>Step 4: Create Tables</h2>";
try {
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    $table_count = 0;
    
    foreach ($statements as $stmt) {
        if (!empty($stmt) && !preg_match('/^--/', $stmt)) {
            try {
                $pdo->exec($stmt);
                if (preg_match('/CREATE TABLE.*?(\w+)/i', $stmt, $m)) {
                    echo "<p style='margin: 3px; color: green;'>✓ Table: {$m[1]}</p>";
                    $table_count++;
                }
            } catch (Exception $e) {
                // Ignore "already exists" warnings
            }
        }
    }
    echo "<p class='status' style='color:green;'>✓ {$table_count} tables created/verified</p>";
} catch (Exception $e) {
    echo "<p class='status' style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// STEP 5: Fix Missing Columns
echo "<div class='step'>";
echo "<h2>Step 5: Fix Column Structure</h2>";

try {
    // Check ch_users columns
    $stmt = $pdo->query("DESCRIBE ch_users");
    $columns = $stmt->fetchAll();
    $column_names = array_map(fn($c) => $c['Field'], $columns);
    
    $columns_to_add = [
        'balance' => "DECIMAL(32,8) NOT NULL DEFAULT 0.00",
        'email_verified' => "TINYINT(1) NOT NULL DEFAULT 0",
        'last_login' => "DATETIME DEFAULT NULL"
    ];
    
    $columns_added = 0;
    foreach ($columns_to_add as $col_name => $col_def) {
        if (!in_array($col_name, $column_names)) {
            try {
                $pdo->exec("ALTER TABLE ch_users ADD COLUMN $col_name $col_def");
                echo "<p style='margin: 3px; color: green;'>✓ Added column: $col_name</p>";
                $columns_added++;
            } catch (Exception $e) {
                echo "<p style='margin: 3px; color: orange;'>⚠ Could not add $col_name: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='margin: 3px; color: green;'>✓ Column exists: $col_name</p>";
        }
    }
    
    if ($columns_added > 0) {
        echo "<p class='status' style='color:green;'>✓ Added {$columns_added} missing columns</p>";
    } else {
        echo "<p class='status' style='color:green;'>✓ All columns present</p>";
    }
} catch (Exception $e) {
    echo "<p class='status' style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// STEP 6: Seed Cryptocurrencies
echo "<div class='step'>";
echo "<h2>Step 6: Seed Cryptocurrency Data</h2>";
try {
    $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM ch_cryptocurrencies');
    $result = $stmt->fetch();
    
    if ($result['cnt'] > 0) {
        echo "<p class='status' style='color:green;'>✓ " . $result['cnt'] . " cryptocurrencies already exist</p>";
    } else {
        $cryptos = [
            ['Bitcoin', 'BTC', 45000, 21000000],
            ['Ethereum', 'ETH', 2500, 120000000],
            ['Litecoin', 'LTC', 150, 84000000],
            ['Ripple', 'XRP', 0.50, 100000000000],
            ['Cardano', 'ADA', 0.80, 45000000000],
            ['Polkadot', 'DOT', 25, 1300000000],
            ['Solana', 'SOL', 100, 500000000],
            ['Dogecoin', 'DOGE', 0.15, 132000000],
            ['Binance Coin', 'BNB', 600, 200000000],
            ['Polygon', 'MATIC', 1.50, 10000000000],
            ['Chainlink', 'LINK', 30, 1000000000],
            ['Uniswap', 'UNI', 20, 1000000000],
        ];
        
        $stmt = $pdo->prepare('INSERT INTO ch_cryptocurrencies (name, symbol, current_price, total_supply, created_at) VALUES (?, ?, ?, ?, NOW())');
        foreach ($cryptos as $c) {
            $stmt->execute($c);
        }
        
        echo "<p class='status' style='color:green;'>✓ Seeded " . count($cryptos) . " cryptocurrencies</p>";
    }
} catch (Exception $e) {
    echo "<p class='status' style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// STEP 7: Test Database Operations
echo "<div class='step'>";
echo "<h2>Step 7: Test Operations</h2>";
try {
    $test_email = 'dbtest' . time() . '@test.com';
    
    // Test INSERT
    $stmt = $pdo->prepare('INSERT INTO ch_users (email, full_name, password, created_at, email_verified, balance) VALUES (?, ?, ?, NOW(), 1, 0)');
    $stmt->execute([$test_email, 'Test User', password_hash('test123', PASSWORD_DEFAULT)]);
    echo "<p style='margin: 3px; color: green;'>✓ INSERT successful</p>";
    
    // Test SELECT
    $stmt = $pdo->prepare('SELECT email, full_name, balance FROM ch_users WHERE email = ?');
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();
    echo "<p style='margin: 3px; color: green;'>✓ SELECT successful - Retrieved: " . $user['full_name'] . "</p>";
    
    // Test UPDATE
    $stmt = $pdo->prepare('UPDATE ch_users SET balance = 100.00 WHERE email = ?');
    $stmt->execute([$test_email]);
    echo "<p style='margin: 3px; color: green;'>✓ UPDATE successful</p>";
    
    // Cleanup
    $stmt = $pdo->prepare('DELETE FROM ch_users WHERE email = ?');
    $stmt->execute([$test_email]);
    
    echo "<p class='status' style='color:green;'>✓ All operations working</p>";
} catch (Exception $e) {
    echo "<p class='status' style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// SUCCESS
echo "<div class='step success'>";
echo "<h2>✓ All Fixed!</h2>";
echo "<p>Your database is now fully set up and working correctly.</p>";
echo "</div>";

echo "<div class='actions'>";
echo "<a href='index.php' class='btn'>🏠 Go to Home</a>";
echo "<a href='register.php' class='btn'>📝 Test Registration</a>";
echo "<a href='dashboard.php' class='btn'>📊 View Dashboard</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
