<?php
/**
 * Database helper functions for real-time crypto data and user financial data
 */

require_once __DIR__ . '/db.php';

/**
 * Get all active cryptocurrencies from database
 * Returns array of [symbol, name, current_price, change_24h, change_7d]
 */
function getAllCryptocurrencies()
{
    $pdo = getDB();
    $cryptos = [];
    
    if ($pdo) {
        try {
            $stmt = $pdo->query('SELECT symbol, name, current_price, change_24h, change_7d FROM ch_cryptocurrencies WHERE status = "active" ORDER BY symbol ASC');
            $cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (Exception $e) {
            error_log('Error fetching cryptocurrencies: ' . $e->getMessage());
        }
    }
    
    return $cryptos;
}

/**
 * Get cryptocurrency by symbol
 */
function getCryptocurrencyBySymbol($symbol)
{
    $pdo = getDB();
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM ch_cryptocurrencies WHERE symbol = :symbol AND status = "active" LIMIT 1');
            $stmt->execute([':symbol' => strtoupper($symbol)]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching cryptocurrency: ' . $e->getMessage());
        }
    }
    
    return null;
}

/**
 * Get user's current balance from database
 */
function getUserBalance($email)
{
    $pdo = getDB();
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT balance FROM ch_users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return floatval($row['balance']);
            }
        } catch (Exception $e) {
            error_log('Error fetching user balance: ' . $e->getMessage());
        }
    }
    
    return 0.0;
}

/**
 * Get user's total investments value
 */
function getUserTotalInvestments($email)
{
    $pdo = getDB();
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('
                SELECT SUM(amount_invested) as total 
                FROM ch_investments 
                WHERE user_email = :email AND status = "active"
            ');
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['total']) {
                return floatval($row['total']);
            }
        } catch (Exception $e) {
            error_log('Error fetching user investments: ' . $e->getMessage());
        }
    }
    
    return 0.0;
}

/**
 * Get user's active investments with current prices
 */
function getUserInvestments($email)
{
    $pdo = getDB();
    $investments = [];
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('
                SELECT inv_id, cryptocurrency, amount_invested, coins_purchased, price_at_purchase, investment_type, status, created_at
                FROM ch_investments 
                WHERE user_email = :email AND status = "active"
                ORDER BY created_at DESC
            ');
            $stmt->execute([':email' => $email]);
            $investments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (Exception $e) {
            error_log('Error fetching user investments: ' . $e->getMessage());
        }
    }
    
    return $investments;
}

/**
 * Get user's recent transactions
 */
function getUserTransactions($email, $limit = 10)
{
    $pdo = getDB();
    $transactions = [];
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('
                SELECT tx_id, type, amount, currency, from_to, created_at, status
                FROM ch_transactions 
                WHERE user_email = :email
                ORDER BY created_at DESC
                LIMIT :limit
            ');
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (Exception $e) {
            error_log('Error fetching user transactions: ' . $e->getMessage());
        }
    }
    
    return $transactions;
}

/**
 * Add transaction to database
 */
function addTransaction($userEmail, $type, $amount, $currency, $txId = null)
{
    $pdo = getDB();
    
    if (!$pdo) return false;
    
    try {
        if (!$txId) {
            $txId = 'TXN-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
        }
        
        $stmt = $pdo->prepare('
            INSERT INTO ch_transactions (tx_id, user_email, type, amount, currency, created_at, status)
            VALUES (:tx_id, :user_email, :type, :amount, :currency, NOW(), :status)
        ');
        
        return $stmt->execute([
            ':tx_id' => $txId,
            ':user_email' => $userEmail,
            ':type' => $type,
            ':amount' => $amount,
            ':currency' => $currency,
            ':status' => 'completed'
        ]);
    } catch (Exception $e) {
        error_log('Error adding transaction: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update user balance
 */
function updateUserBalance($email, $newBalance)
{
    $pdo = getDB();
    
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare('UPDATE ch_users SET balance = :balance WHERE email = :email');
        return $stmt->execute([':balance' => $newBalance, ':email' => $email]);
    } catch (Exception $e) {
        error_log('Error updating user balance: ' . $e->getMessage());
        return false;
    }
}

/**
 * Add investment to database
 */
function addInvestment($userEmail, $cryptocurrency, $amountInvested, $coinsPurchased, $priceAtPurchase, $investmentType)
{
    $pdo = getDB();
    
    if (!$pdo) return false;
    
    try {
        $invId = 'INV-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
        $maturityDate = null;
        
        if ($investmentType === 'fixed') {
            $maturityDate = date('Y-m-d H:i:s', strtotime('+30 days'));
        }
        
        $stmt = $pdo->prepare('
            INSERT INTO ch_investments 
            (inv_id, user_email, cryptocurrency, amount_invested, coins_purchased, price_at_purchase, investment_type, status, created_at, maturity_date)
            VALUES (:inv_id, :user_email, :crypto, :amount, :coins, :price, :type, :status, NOW(), :maturity)
        ');
        
        return $stmt->execute([
            ':inv_id' => $invId,
            ':user_email' => $userEmail,
            ':crypto' => $cryptocurrency,
            ':amount' => $amountInvested,
            ':coins' => $coinsPurchased,
            ':price' => $priceAtPurchase,
            ':type' => $investmentType,
            ':status' => 'active',
            ':maturity' => $maturityDate
        ]);
    } catch (Exception $e) {
        error_log('Error adding investment: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get portfolio statistics
 */
function getPortfolioStats($email)
{
    $balance = getUserBalance($email);
    $totalInvested = getUserTotalInvestments($email);
    
    return [
        'balance' => $balance,
        'total_invested' => $totalInvested,
        'total_value' => $balance + $totalInvested,
        'portfolio_health' => ($balance > 0) ? 'Healthy' : 'Low Balance'
    ];
}

?>
