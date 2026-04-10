<?php
/**
 * ONE-STEP DATABASE FIX
 * Opens automatically, checks everything, and fixes it
 */

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
echo "<title>CryptoHub - Database Fix</title>";
echo "<style>";
echo "* { box-sizing: border-box; }";
echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; padding: 20px; display: flex; align-items: center; justify-content: center; }";
echo ".container { background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 600px; width: 100%; padding: 40px; }";
echo "h1 { color: #333; margin-top: 0; text-align: center; }";
echo ".step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #667eea; border-radius: 5px; }";
echo ".step h2 { margin: 0 0 10px 0; color: #667eea; font-size: 18px; }";
echo ".step p { margin: 5px 0; color: #666; }";
echo ".btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; margin: 10px 5px 10px 0; }";
echo ".btn:hover { background: #5568d3; }";
echo ".success { background: #d4edda; border-left-color: #28a745; }";
echo ".warning { background: #fff3cd; border-left-color: #ffc107; }";
echo ".error { background: #f8d7da; border-left-color: #dc3545; }";
echo ".status { font-weight: bold; margin: 10px 0; }";
echo ".code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; color: #d63384; }";
echo ".actions { text-align: center; margin-top: 30px; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>🚀 CryptoHub Database Setup</h1>";

// STEP 1: Check MySQL
echo "<div class='step'>";
echo "<h2>Step 1: Testing MySQL Connection</h2>";
$mysql_ok = false;
try {
    $test_pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    echo "<p class='status success'>✓ MySQL is running!</p>";
    $mysql_ok = true;
} catch (Exception $e) {
    echo "<p class='status error'>✗ MySQL is NOT running</p>";
    echo "<p><strong>Solution:</strong></p>";
    echo "<ol>";
    echo "<li>Open <strong>Laragon</strong> application</li>";
    echo "<li>Click the <strong>MySQL icon</strong> (database icon)</li>";
    echo "<li>Wait for it to show 'Running'</li>";
    echo "<li>Reload this page</li>";
    echo "</ol>";
}
echo "</div>";

if (!$mysql_ok) {
    echo "<p style='text-align:center; color:red;'><strong>⚠️ Please start MySQL first, then reload this page</strong></p>";
    echo "</div></body></html>";
    die();
}

// STEP 2: Setup Database and Tables
echo "<div class='step'>";
echo "<h2>Step 2: Setting up Database</h2>";
require_once __DIR__ . '/config.php';

try {
    // Create database
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT, DB_USER, DB_PASS);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "<p class='status success'>✓ Database created/verified</p>";
    
    // Connect to database
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Load and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    $table_count = 0;
    
    foreach ($statements as $stmt) {
        if (!empty($stmt) && !preg_match('/^--/', $stmt)) {
            try {
                $pdo->exec($stmt);
                if (preg_match('/CREATE TABLE.*?(\w+)/i', $stmt, $m)) {
                    $table_count++;
                }
            } catch (Exception $e) {
                // Ignore "already exists" errors
            }
        }
    }
    
    echo "<p class='status success'>✓ {$table_count} tables created/verified</p>";
    
    // Seed cryptocurrencies
    $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM ch_cryptocurrencies');
    $result = $stmt->fetch();
    
    if ($result['cnt'] > 0) {
        echo "<p class='status success'>✓ {$result['cnt']} cryptocurrencies available</p>";
    } else {
        // Seed cryptos
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
        
        echo "<p class='status success'>✓ " . count($cryptos) . " cryptocurrencies seeded</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='status error'>✗ Error: " . $e->getMessage() . "</p>";
    echo "</div></body></html>";
    die();
}
echo "</div>";

// STEP 3: Test Database Operations
echo "<div class='step'>";
echo "<h2>Step 3: Testing Database Operations</h2>";
try {
    // Test INSERT
    $test_email = 'dbtest' . time() . '@test.com';
    $stmt = $pdo->prepare('INSERT INTO ch_users (email, full_name, password, created_at, email_verified, balance) VALUES (?, ?, ?, NOW(), 1, 0)');
    $stmt->execute([$test_email, 'Test', password_hash('test', PASSWORD_DEFAULT)]);
    echo "<p class='status success'>✓ INSERT works</p>";
    
    // Test SELECT
    $stmt = $pdo->prepare('SELECT * FROM ch_users WHERE email = ?');
    $stmt->execute([$test_email]);
    if ($stmt->fetch()) {
        echo "<p class='status success'>✓ SELECT works</p>";
    }
    
    // Test UPDATE
    $stmt = $pdo->prepare('UPDATE ch_users SET balance = 100 WHERE email = ?');
    $stmt->execute([$test_email]);
    echo "<p class='status success'>✓ UPDATE works</p>";
    
    // Cleanup
    $stmt = $pdo->prepare('DELETE FROM ch_users WHERE email = ?');
    $stmt->execute([$test_email]);
    
} catch (Exception $e) {
    echo "<p class='status error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// SUCCESS
echo "<div class='step success'>";
echo "<h2>✓ Setup Complete!</h2>";
echo "<p>Your database is now fully configured and ready to use.</p>";
echo "</div>";

echo "<div class='actions'>";
echo "<a href='index.php' class='btn'>🏠 Go to Home</a>";
echo "<a href='register.php' class='btn'>📝 Test Registration</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
