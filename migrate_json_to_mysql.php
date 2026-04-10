<?php
/**
 * Migration script: import existing JSON users and admin-users into MySQL.
 * Run from CLI: php migrate_json_to_mysql.php
 */
require_once __DIR__ . '/db.php';

$pdo = getDB();
if (!$pdo) {
    echo "Database connection unavailable. Check config.php and MySQL server.\n";
    exit(1);
}

function insertUser($pdo, $email, $data)
{
    $sql = "INSERT INTO ch_users (email, full_name, phone, password, created_at, email_verified) VALUES (:email, :full_name, :phone, :password, :created_at, :email_verified) ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), phone=VALUES(phone), password=VALUES(password), email_verified=VALUES(email_verified)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':full_name' => $data['fullName'] ?? '',
        ':phone' => $data['phone'] ?? null,
        ':password' => $data['password'] ?? '',
        ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ':email_verified' => !empty($data['email_verified']) ? 1 : 0,
    ]);
}

function insertAdmin($pdo, $email, $data)
{
    $sql = "INSERT INTO ch_admins (email, full_name, phone, password, created_at, status) VALUES (:email, :full_name, :phone, :password, :created_at, :status) ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), phone=VALUES(phone), password=VALUES(password), status=VALUES(status)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':full_name' => $data['fullName'] ?? '',
        ':phone' => $data['phone'] ?? null,
        ':password' => $data['password'] ?? '',
        ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ':status' => $data['status'] ?? 'active',
    ]);
}

function insertTransaction($pdo, $tx_data)
{
    $sql = "INSERT INTO ch_transactions (tx_id, user_email, type, amount, currency, from_to, created_at, status) VALUES (:tx_id, :user_email, :type, :amount, :currency, :from_to, :created_at, :status) ON DUPLICATE KEY UPDATE amount=VALUES(amount), status=VALUES(status)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tx_id' => $tx_data['id'] ?? '',
        ':user_email' => $tx_data['user_email'] ?? '',
        ':type' => $tx_data['type'] ?? 'add_money',
        ':amount' => floatval($tx_data['amount'] ?? 0),
        ':currency' => 'USD',
        ':from_to' => isset($tx_data['payment_details']) ? ($tx_data['payment_details']['method'] . '|' . $tx_data['payment_details']['last_digits']) : '',
        ':created_at' => $tx_data['timestamp'] ?? date('Y-m-d H:i:s'),
        ':status' => $tx_data['status'] ?? 'completed',
    ]);
}

function insertInvestment($pdo, $inv_data)
{
    $sql = "INSERT INTO ch_investments (inv_id, user_email, cryptocurrency, amount_invested, coins_purchased, price_at_purchase, investment_type, status, created_at, maturity_date) VALUES (:inv_id, :user_email, :cryptocurrency, :amount_invested, :coins_purchased, :price_at_purchase, :investment_type, :status, :created_at, :maturity_date) ON DUPLICATE KEY UPDATE amount_invested=VALUES(amount_invested), status=VALUES(status)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':inv_id' => $inv_data['id'] ?? '',
        ':user_email' => $inv_data['user_email'] ?? '',
        ':cryptocurrency' => $inv_data['cryptocurrency'] ?? '',
        ':amount_invested' => floatval($inv_data['amount_invested'] ?? 0),
        ':coins_purchased' => floatval($inv_data['coins_purchased'] ?? 0),
        ':price_at_purchase' => floatval($inv_data['price_at_purchase'] ?? 0),
        ':investment_type' => $inv_data['investment_type'] ?? 'spot',
        ':status' => $inv_data['status'] ?? 'active',
        ':created_at' => $inv_data['created_at'] ?? date('Y-m-d H:i:s'),
        ':maturity_date' => $inv_data['maturity_date'] ?? null,
    ]);
}

function insertMessage($pdo, $msg_data)
{
    $sql = "INSERT INTO ch_messages (user_email, user_name, subject, message, ip_address, status, created_at) VALUES (:user_email, :user_name, :subject, :message, :ip_address, :status, :created_at)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_email' => $msg_data['user_email'] ?? '',
        ':user_name' => $msg_data['user_name'] ?? '',
        ':subject' => $msg_data['subject'] ?? '',
        ':message' => $msg_data['message'] ?? '',
        ':ip_address' => $msg_data['ip_address'] ?? null,
        ':status' => 'unread',
        ':created_at' => $msg_data['timestamp'] ?? date('Y-m-d H:i:s'),
    ]);
}

$usersFile = __DIR__ . '/users.json';
if (file_exists($usersFile)) {
    $raw = file_get_contents($usersFile);
    $users = json_decode($raw, true);
    if (is_array($users)) {
        echo "Importing users...\n";
        $count = 0;
        foreach ($users as $email => $data) {
            try {
                insertUser($pdo, $email, $data);
                // Also update balance if it exists in the user data
                if (isset($data['balance'])) {
                    $upd = $pdo->prepare('UPDATE ch_users SET balance = :balance WHERE email = :email');
                    $upd->execute([':balance' => floatval($data['balance']), ':email' => $email]);
                }
                $count++;
            } catch (Exception $e) {
                echo "Failed to import user $email: " . $e->getMessage() . "\n";
            }
        }
        echo "Imported $count users.\n";
    } else {
        echo "No users to import (invalid JSON).\n";
    }
} else {
    echo "users.json not found, skipping users import.\n";
}

$adminsFile = __DIR__ . '/admin-users.json';
if (file_exists($adminsFile)) {
    $raw = file_get_contents($adminsFile);
    $admins = json_decode($raw, true);
    if (is_array($admins)) {
        echo "Importing admins...\n";
        $count = 0;
        foreach ($admins as $email => $data) {
            try {
                insertAdmin($pdo, $email, $data);
                $count++;
            } catch (Exception $e) {
                echo "Failed to import admin $email: " . $e->getMessage() . "\n";
            }
        }
        echo "Imported $count admins.\n";
    } else {
        echo "No admins to import (invalid JSON).\n";
    }
} else {
    echo "admin-users.json not found, skipping admins import.\n";
}

// Import transactions
$transactionsFile = __DIR__ . '/transactions/movements.json';
if (file_exists($transactionsFile)) {
    $raw = file_get_contents($transactionsFile);
    $transactions = json_decode($raw, true);
    if (is_array($transactions)) {
        echo "Importing transactions...\n";
        $count = 0;
        foreach ($transactions as $tx) {
            try {
                insertTransaction($pdo, $tx);
                $count++;
            } catch (Exception $e) {
                echo "Failed to import transaction " . ($tx['id'] ?? 'unknown') . ": " . $e->getMessage() . "\n";
            }
        }
        echo "Imported $count transactions.\n";
    }
} else {
    echo "transactions/movements.json not found, skipping transactions import.\n";
}

// Import investments
$investmentsFile = __DIR__ . '/investments/investments.json';
if (file_exists($investmentsFile)) {
    $raw = file_get_contents($investmentsFile);
    $investments = json_decode($raw, true);
    if (is_array($investments)) {
        echo "Importing investments...\n";
        $count = 0;
        foreach ($investments as $inv) {
            try {
                insertInvestment($pdo, $inv);
                $count++;
            } catch (Exception $e) {
                echo "Failed to import investment " . ($inv['id'] ?? 'unknown') . ": " . $e->getMessage() . "\n";
            }
        }
        echo "Imported $count investments.\n";
    }
} else {
    echo "investments/investments.json not found, skipping investments import.\n";
}

// Import contact messages
$messagesFile = __DIR__ . '/contacts/messages.json';
if (file_exists($messagesFile)) {
    $raw = file_get_contents($messagesFile);
    $messages = json_decode($raw, true);
    if (is_array($messages)) {
        echo "Importing contact messages...\n";
        $count = 0;
        foreach ($messages as $msg) {
            try {
                insertMessage($pdo, $msg);
                $count++;
            } catch (Exception $e) {
                echo "Failed to import message: " . $e->getMessage() . "\n";
            }
        }
        echo "Imported $count messages.\n";
    }
} else {
    echo "contacts/messages.json not found, skipping messages import.\n";
}

echo "Migration complete.\n";
