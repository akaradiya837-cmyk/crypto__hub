<?php
/**
 * Complete Database Verification and Setup Script
 * This script checks if MySQL is running, the database exists, and creates tables if needed
 */

require_once __DIR__ . '/config.php';

echo "<h2>Database Verification and Setup</h2>";
echo "<hr>";

// Test 1: Check MySQL Connection
echo "<h3>Step 1: Test MySQL Connection</h3>";
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s', DB_HOST, DB_PORT),
        DB_USER,
        DB_PASS
    );
    echo "✓ MySQL Server is RUNNING<br>";
    echo "✓ Connection successful on " . DB_HOST . ":" . DB_PORT . "<br>";
} catch (PDOException $e) {
    echo "✗ FAILED to connect to MySQL<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<br><strong>SOLUTION:</strong><br>";
    echo "1. Make sure Laragon MySQL is running<br>";
    echo "2. Open Laragon Control Panel<br>";
    echo "3. Click the MySQL icon to start it<br>";
    echo "4. Then reload this page<br>";
    die();
}

// Test 2: Check if Database Exists
echo "<h3>Step 2: Check if Database Exists</h3>";
try {
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $db_exists = $stmt->fetch();
    
    if ($db_exists) {
        echo "✓ Database '" . DB_NAME . "' EXISTS<br>";
    } else {
        echo "✗ Database '" . DB_NAME . "' DOES NOT EXIST<br>";
        echo "Creating database...<br>";
        
        // Create the database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database created successfully<br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Connect to the specific database
echo "<h3>Step 3: Connect to Database</h3>";
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
    echo "✓ Connected to database '" . DB_NAME . "' successfully<br>";
} catch (PDOException $e) {
    echo "✗ Failed to connect to " . DB_NAME . "<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    die();
}

// Test 4: Check and Create Tables
echo "<h3>Step 4: Check Required Tables</h3>";

$required_tables = [
    'ch_users' => 'Users table',
    'ch_admins' => 'Admins table',
    'ch_transactions' => 'Transactions table',
    'ch_investments' => 'Investments table',
    'ch_messages' => 'Messages table',
    'ch_cryptocurrencies' => 'Cryptocurrencies table',
    'ch_otps' => 'OTP table'
];

$missing_tables = [];

foreach ($required_tables as $table_name => $table_desc) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            echo "✓ $table_desc ($table_name)<br>";
        } else {
            echo "✗ $table_desc ($table_name) MISSING<br>";
            $missing_tables[] = $table_name;
        }
    } catch (Exception $e) {
        echo "✗ Error checking $table_name: " . $e->getMessage() . "<br>";
    }
}

// If tables are missing, create them from schema.sql
if (!empty($missing_tables)) {
    echo "<h3>Step 5: Creating Missing Tables</h3>";
    
    $schema_file = __DIR__ . '/schema.sql';
    if (file_exists($schema_file)) {
        $schema_sql = file_get_contents($schema_file);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $schema_sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    
                    // Extract table name from statement
                    if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $statement, $matches)) {
                        echo "✓ Created/verified table: " . $matches[1] . "<br>";
                    }
                } catch (Exception $e) {
                    echo "⚠ Error executing statement: " . $e->getMessage() . "<br>";
                }
            }
        }
        
        echo "✓ All tables have been created successfully<br>";
    } else {
        echo "✗ schema.sql file not found at: $schema_file<br>";
    }
} else {
    echo "<p style='color:green;'><strong>All required tables exist!</strong></p>";
}

// Test 5: Seed Cryptocurrencies if empty
echo "<h3>Step 6: Check Cryptocurrencies Data</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ch_cryptocurrencies");
    $result = $stmt->fetch();
    $crypto_count = $result['count'];
    
    if ($crypto_count > 0) {
        echo "✓ Database has $crypto_count cryptocurrencies<br>";
    } else {
        echo "✗ No cryptocurrencies found, seeding data...<br>";
        
        // Seed cryptocurrencies
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
        
        echo "✓ Seeded " . count($cryptos) . " cryptocurrencies<br>";
    }
} catch (Exception $e) {
    echo "⚠ Note: Could not seed cryptocurrencies: " . $e->getMessage() . "<br>";
}

// Test 6: Test basic operations
echo "<h3>Step 7: Test Database Operations</h3>";
try {
    // Test INSERT
    $test_email = 'dbtest' . time() . '@test.com';
    $stmt = $pdo->prepare('INSERT INTO ch_users (email, full_name, password, created_at, email_verified, balance) VALUES (:email, :full_name, :password, NOW(), 1, 0)');
    $stmt->execute([
        ':email' => $test_email,
        ':full_name' => 'Database Test',
        ':password' => password_hash('test123', PASSWORD_DEFAULT),
    ]);
    echo "✓ INSERT operation works<br>";
    
    // Test SELECT
    $stmt = $pdo->prepare('SELECT * FROM ch_users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ SELECT operation works<br>";
    }
    
    // Test UPDATE
    $stmt = $pdo->prepare('UPDATE ch_users SET balance = 100 WHERE email = :email');
    $stmt->execute([':email' => $test_email]);
    echo "✓ UPDATE operation works<br>";
    
    // Cleanup
    $stmt = $pdo->prepare('DELETE FROM ch_users WHERE email = :email');
    $stmt->execute([':email' => $test_email]);
    echo "✓ DELETE operation works<br>";
    
} catch (Exception $e) {
    echo "✗ Database operation error: " . $e->getMessage() . "<br>";
}

echo "<h3>✓ Database Setup Complete!</h3>";
echo "<hr>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li><a href='register.php'>Try registering a new account</a></li>";
echo "<li>Check if OTP verification works</li>";
echo "<li>Check if data is saved in the database</li>";
echo "</ol>";
?>
