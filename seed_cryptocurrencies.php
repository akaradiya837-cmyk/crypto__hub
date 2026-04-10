<?php
require_once __DIR__ . '/db.php';

/**
 * Seed cryptocurrencies into ch_cryptocurrencies table
 * Run once to populate available cryptocurrencies
 */

$pdo = getDB();
if (!$pdo) {
    die('ERROR: Database connection failed. Please ensure MySQL is running and configured in config.php');
}

// Sample cryptocurrencies with initial prices (data as of early 2024)
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
    // Clear existing data (optional)
    $pdo->exec('TRUNCATE TABLE ch_cryptocurrencies');
    
    // Insert cryptocurrencies
    $stmt = $pdo->prepare('
        INSERT INTO ch_cryptocurrencies (symbol, name, current_price, change_24h, change_7d, status)
        VALUES (:symbol, :name, :price, :change_24h, :change_7d, :status)
    ');
    
    $inserted = 0;
    foreach ($cryptos as $crypto) {
        $stmt->execute([
            ':symbol' => $crypto['symbol'],
            ':name' => $crypto['name'],
            ':price' => $crypto['price'],
            ':change_24h' => $crypto['change_24h'],
            ':change_7d' => $crypto['change_7d'],
            ':status' => 'active'
        ]);
        $inserted++;
    }
    
    echo "✓ Successfully seeded {$inserted} cryptocurrencies into the database!\n";
    echo "You can now view these cryptocurrencies in Invest and Add Money pages.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    die;
}
?>
