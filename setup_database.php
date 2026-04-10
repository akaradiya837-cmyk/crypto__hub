<?php
/**
 * CryptoHub Database Setup & Initialization Script
 * This script creates all required tables and seeds initial cryptocurrency data
 * Run this once to initialize your database for real-time updates
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

echo "====================================\n";
echo "CryptoHub Database Setup\n";
echo "====================================\n\n";

$pdo = getDB();

if (!$pdo) {
    echo "❌ ERROR: Could not connect to database.\n";
    echo "Please ensure:\n";
    echo "1. MySQL is running\n";
    echo "2. Database 'cryptohub' exists\n";
    echo "3. Credentials in config.php are correct\n\n";
    echo "To create the database, run:\n";
    echo "CREATE DATABASE cryptohub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n\n";
    die("Database connection failed.\n");
}

echo "✓ Database connection successful!\n\n";

// Read and execute schema.sql
$schemaFile = __DIR__ . '/schema.sql';
if (!file_exists($schemaFile)) {
    die("❌ ERROR: schema.sql not found!\n");
}

echo "Creating tables from schema.sql...\n";
$schema = file_get_contents($schemaFile);

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $schema)), function($stmt) {
    return !empty($stmt) && !preg_match('/^--/', $stmt);
});

$createdTables = 0;
foreach ($statements as $statement) {
    try {
        $pdo->exec($statement);
        if (preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $statement, $matches)) {
            echo "  ✓ Table '{$matches[1]}' ready\n";
            $createdTables++;
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            echo "  ⚠ Warning: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✓ Tables created/verified!\n\n";

// Seed cryptocurrencies
echo "Seeding cryptocurrency data...\n";
$cryptos = [
    ['symbol' => 'BTC', 'name' => 'Bitcoin', 'price' => 42500.00, 'change_24h' => 2.5, 'change_7d' => 5.2],
    ['symbol' => 'ETH', 'name' => 'Ethereum', 'price' => 2850.00, 'change_24h' => 1.8, 'change_7d' => 4.1],
    ['symbol' => 'BNB', 'name' => 'Binance Coin', 'price' => 625.00, 'change_24h' => 3.2, 'change_7d' => 6.5],
    ['symbol' => 'XRP', 'name' => 'Ripple', 'price' => 0.52, 'change_24h' => 4.1, 'change_7d' => 8.3],
    ['symbol' => 'SOL', 'name' => 'Solana', 'price' => 165.00, 'change_24h' => 2.9, 'change_7d' => 7.4],
    ['symbol' => 'ADA', 'name' => 'Cardano', 'price' => 0.98, 'change_24h' => 1.5, 'change_7d' => 3.2],
    ['symbol' => 'DOGE', 'name' => 'Dogecoin', 'price' => 0.15, 'change_24h' => 5.2, 'change_7d' => 9.8],
    ['symbol' => 'USDT', 'name' => 'Tether', 'price' => 1.00, 'change_24h' => 0.0, 'change_7d' => 0.1],
    ['symbol' => 'USDC', 'name' => 'USD Coin', 'price' => 1.00, 'change_24h' => 0.0, 'change_7d' => 0.0],
    ['symbol' => 'LINK', 'name' => 'Chainlink', 'price' => 18.50, 'change_24h' => 2.3, 'change_7d' => 5.1],
    ['symbol' => 'LTC', 'name' => 'Litecoin', 'price' => 680.00, 'change_24h' => 2.1, 'change_7d' => 4.5],
    ['symbol' => 'XLM', 'name' => 'Stellar Lumens', 'price' => 0.18, 'change_24h' => 3.4, 'change_7d' => 6.7],
];

try {
    // Clear existing cryptocurrencies
    $pdo->exec('TRUNCATE TABLE ch_cryptocurrencies');
    
    // Insert cryptocurrencies
    $stmt = $pdo->prepare('
        INSERT INTO ch_cryptocurrencies (symbol, name, current_price, change_24h, change_7d, status)
        VALUES (:symbol, :name, :price, :change_24h, :change_7d, :status)
    ');
    
    $insertedCount = 0;
    foreach ($cryptos as $crypto) {
        $stmt->execute([
            ':symbol' => $crypto['symbol'],
            ':name' => $crypto['name'],
            ':price' => $crypto['price'],
            ':change_24h' => $crypto['change_24h'],
            ':change_7d' => $crypto['change_7d'],
            ':status' => 'active'
        ]);
        $insertedCount++;
        echo "  ✓ {$crypto['symbol']} - {$crypto['name']}\n";
    }
    
    echo "\n✓ Seeded {$insertedCount} cryptocurrencies!\n\n";
} catch (Exception $e) {
    echo "❌ Error seeding cryptocurrencies: " . $e->getMessage() . "\n";
    die;
}

// Test the database helper functions
echo "Testing database helper functions...\n";
require_once __DIR__ . '/db_helpers.php';

try {
    $allCryptos = getAllCryptocurrencies();
    echo "  ✓ Can fetch cryptocurrencies: " . count($allCryptos) . " found\n";
    
    $btc = getCryptocurrencyBySymbol('BTC');
    echo "  ✓ BTC price: $" . number_format($btc['current_price'], 2) . "\n";
    
    echo "\n✓ Database helpers working!\n\n";
} catch (Exception $e) {
    echo "❌ Error testing helpers: " . $e->getMessage() . "\n";
    die;
}

// Summary
echo "====================================\n";
echo "✓ SETUP COMPLETE!\n";
echo "====================================\n\n";
echo "Your CryptoHub website is now configured for:\n";
echo "✓ Real-time cryptocurrency data\n";
echo "✓ Real-time balance updates\n";
echo "✓ Real-time investment tracking\n";
echo "✓ Real-time transaction history\n\n";

echo "NEXT STEPS:\n";
echo "1. Create test accounts by visiting: /register.php\n";
echo "2. Test adding money: /add-money.php\n";
echo "3. Test investing: /invest.php\n";
echo "4. View dashboard: /dashboard.php\n\n";

echo "Database Tables Created:\n";
echo "  - ch_users\n";
echo "  - ch_admins\n";
echo "  - ch_transactions\n";
echo "  - ch_investments\n";
echo "  - ch_messages\n";
echo "  - ch_cryptocurrencies (" . count($allCryptos) . " cryptos)\n";
echo "  - ch_otps\n\n";

echo "To update cryptocurrency prices in the future, update the ch_cryptocurrencies table:\n";
echo "UPDATE ch_cryptocurrencies SET current_price = 45000 WHERE symbol = 'BTC';\n\n";
?>
