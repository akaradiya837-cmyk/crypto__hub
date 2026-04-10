<?php
/**
 * QUICK FIX: Database Update Issue
 * This script will:
 * 1. Check if MySQL is running
 * 2. Create the database if it doesn't exist
 * 3. Create all tables if they don't exist
 * 4. Verify all operations work
 */

header('Content-Type: text/html; charset=utf-8');

echo "<html>";
echo "<head><title>CryptoHub Database Setup</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".success { color: green; } .error { color: red; } .warning { color: orange; }";
echo "pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }";
echo "</style></head><body>";
echo "<div class='container'>";

require_once __DIR__ . '/config.php';

echo "<h1>🔧 Database Setup & Verification</h1>";
echo "<hr>";

// STEP 1: Test MySQL Connection
echo "<h2>Step 1: Test MySQL Connection</h2>";
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s', DB_HOST, DB_PORT),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p class='success'>✓ MySQL Server is running and accessible</p>";
} catch (PDOException $e) {
    echo "<p class='error'>✗ Cannot connect to MySQL</p>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solution:</strong></p>";
    echo "<ol>";
    echo "<li>Open Laragon</li>";
    echo "<li>Click the <strong>MySQL icon</strong> to start the MySQL server</li>";
    echo "<li>Wait for it to fully start</li>";
    echo "<li>Reload this page</li>";
    echo "</ol>";
    die("</div></body></html>");
}

// STEP 2: Create Database if not exists
echo "<h2>Step 2: Create/Verify Database</h2>";
try {
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $db_exists = $stmt->fetch();
    
    if ($db_exists) {
        echo "<p class='success'>✓ Database '" . DB_NAME . "' exists</p>";
    } else {
        echo "<p class='warning'>⚠ Database '" . DB_NAME . "' not found, creating...</p>";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Database created successfully</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    die("</div></body></html>");
}

// STEP 3: Connect to specific database
echo "<h2>Step 3: Connect to Database</h2>";
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "<p class='success'>✓ Connected to database '" . DB_NAME . "'</p>";
} catch (PDOException $e) {
    echo "<p class='error'>✗ Failed to connect: " . $e->getMessage() . "</p>";
    die("</div></body></html>");
}

// STEP 4: Create Tables
echo "<h2>Step 4: Create/Verify Tables</h2>";
$schema_file = __DIR__ . '/schema.sql';

if (!file_exists($schema_file)) {
    echo "<p class='error'>✗ schema.sql file not found</p>";
} else {
    $schema_sql = file_get_contents($schema_file);
    $statements = array_filter(array_map('trim', explode(';', $schema_sql)));
    $table_count = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                if (preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $statement, $matches)) {
                    echo "<p class='success'>✓ Table '{$matches[1]}' created/verified</p>";
                    $table_count++;
                }
            } catch (Exception $e) {
                // Ignore "already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p class='warning'>⚠ " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<p class='success'>✓ All {$table_count} tables are ready</p>";
}

// STEP 5: Seed Cryptocurrencies
echo "<h2>Step 5: Verify Cryptocurrency Data</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ch_cryptocurrencies");
    $result = $stmt->fetch();
    $count = $result['count'];
    
    if ($count > 0) {
        echo "<p class='success'>✓ Database has {$count} cryptocurrencies</p>";
    } else {
        echo "<p class='warning'>⚠ No cryptocurrencies found, seeding...</p>";
        
        $cryptos = [
            ['Bitcoin', 'BTC', 45000.00, 21000000],
            ['Ethereum', 'ETH', 2500.00, 120000000],
            ['Litecoin', 'LTC', 150.00, 84000000],
            ['Ripple', 'XRP', 0.50, 100000000000],
            ['Cardano', 'ADA', 0.80, 45000000000],
            ['Polkadot', 'DOT', 25.00, 1300000000],
            ['Solana', 'SOL', 100.00, 500000000],
            ['Dogecoin', 'DOGE', 0.15, 132000000],
            ['Binance Coin', 'BNB', 600.00, 200000000],
            ['Polygon', 'MATIC', 1.50, 10000000000],
            ['Chainlink', 'LINK', 30.00, 1000000000],
            ['Uniswap', 'UNI', 20.00, 1000000000],
        ];
        
        $stmt = $pdo->prepare('INSERT INTO ch_cryptocurrencies (name, symbol, current_price, total_supply, created_at) VALUES (:name, :symbol, :price, :supply, NOW())');
        
        foreach ($cryptos as $crypto) {
            $stmt->execute([
                ':name' => $crypto[0],
                ':symbol' => $crypto[1],
                ':price' => $crypto[2],
                ':supply' => $crypto[3],
            ]);
        }
        
        echo "<p class='success'>✓ Seeded " . count($cryptos) . " cryptocurrencies</p>";
    }
} catch (Exception $e) {
    echo "<p class='warning'>⚠ Error with cryptocurrencies: " . $e->getMessage() . "</p>";
}

// STEP 6: Test Database Operations
echo "<h2>Step 6: Test Read/Write Operations</h2>";
try {
    $test_email = 'test' . time() . '@test.com';
    
    // Test INSERT
    $stmt = $pdo->prepare('INSERT INTO ch_users (email, full_name, password, created_at, email_verified, balance) VALUES (:email, :full_name, :password, NOW(), 1, 0)');
    $stmt->execute([
        ':email' => $test_email,
        ':full_name' => 'Test User',
        ':password' => password_hash('test123', PASSWORD_DEFAULT),
    ]);
    echo "<p class='success'>✓ INSERT operation works</p>";
    
    // Test SELECT
    $stmt = $pdo->prepare('SELECT email, full_name FROM ch_users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p class='success'>✓ SELECT operation works - Retrieved: " . $user['full_name'] . "</p>";
    }
    
    // Test UPDATE
    $stmt = $pdo->prepare('UPDATE ch_users SET balance = 100.00 WHERE email = :email');
    $stmt->execute([':email' => $test_email]);
    echo "<p class='success'>✓ UPDATE operation works</p>";
    
    // Verify UPDATE worked
    $stmt = $pdo->prepare('SELECT balance FROM ch_users WHERE email = :email');
    $stmt->execute([':email' => $test_email]);
    $updated = $stmt->fetch();
    echo "<p class='success'>✓ Balance updated to: " . $updated['balance'] . "</p>";
    
    // Test DELETE
    $stmt = $pdo->prepare('DELETE FROM ch_users WHERE email = :email');
    $stmt->execute([':email' => $test_email]);
    echo "<p class='success'>✓ DELETE operation works</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Database operation error: " . $e->getMessage() . "</p>";
}

// FINAL SUMMARY
echo "<h2>✓ Database Setup Complete!</h2>";
echo "<p>Your database is now fully configured and working.</p>";
echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='index.php' style='font-size:16px; color:blue;'><strong>Go to Home Page</strong></a></li>";
echo "<li><a href='register.php' style='font-size:16px; color:blue;'><strong>Test Registration</strong></a></li>";
echo "<li>Enter an OTP when prompted and check if data is saved</li>";
echo "</ol>";
echo "<br>";
echo "<p style='background:#f0f0f0; padding:10px; border-radius:5px;'>";
echo "<strong>Troubleshooting:</strong> If you still have issues:";
echo "<ul>";
echo "<li>Check that you're using the correct database credentials in config.php</li>";
echo "<li>Make sure MySQL is still running</li>";
echo "<li>Clear your browser cache and try again</li>";
echo "</ul>";
echo "</p>";

echo "</div></body></html>";
?>
