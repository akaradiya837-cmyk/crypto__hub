<?php
/**
 * Database Update Diagnostic
 * Checks if database operations are actually being executed and updating data
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

echo "<h2>Database Update Diagnostic Test</h2>";
echo "<hr>";

// Test 1: Check if DB is enabled
echo "<h3>Test 1: Database Connection Status</h3>";
$pdo = getDB();
if ($pdo) {
    echo "✓ Database is CONNECTED<br>";
    echo "✓ Using MySQL database (NOT JSON fallback)<br>";
} else {
    echo "✗ Database is NOT connected<br>";
    echo "✗ Application is using JSON file fallback<br>";
    echo "<strong>This is the problem! Database operations won't work.</strong>";
 die();
}

// Test 2: Check database credentials
echo "<h3>Test 2: Database Credentials</h3>";
echo "Host: " . DB_HOST . "<br>";
echo "Port: " . DB_PORT . "<br>";
echo "Database: " . DB_NAME . "<br>";
echo "User: " . DB_USER . "<br>";

// Test 3: Test a simple INSERT
echo "<h3>Test 3: Test INSERT Operation</h3>";
$test_email = 'inserttest' . time() . '@test.com';
$test_name = 'Test User ' . time();

try {
    $stmt = $pdo->prepare('INSERT INTO ch_users (email, full_name, phone, password, created_at, email_verified, balance) VALUES (:email, :full_name, :phone, :password, :created_at, :email_verified, :balance)');
    $stmt->execute([
        ':email' => $test_email,
        ':full_name' => $test_name,
        ':phone' => '1234567890',
        ':password' => password_hash('testpass', PASSWORD_DEFAULT),
        ':created_at' => date('Y-m-d H:i:s'),
        ':email_verified' => 0,
        ':balance' => 0.00,
    ]);
    echo "✓ INSERT successful<br>";
    echo "Test user created: $test_email<br>";
} catch (Exception $e) {
    echo "✗ INSERT failed: " . $e->getMessage() . "<br>";
    die();
}

// Test 4: Test a SELECT to verify the insert
echo "<h3>Test 4: Test SELECT Operation</h3>";
try {
    $stmt = $pdo->prepare('SELECT * FROM ch_users WHERE email = :email');
    $stmt->execute([':email' => $test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ SELECT successful<br>";
        echo "Retrieved user: " . $user['full_name'] . " (" . $user['email'] . ")<br>";
    } else {
        echo "✗ User not found after insert!<br>";
    }
} catch (Exception $e) {
    echo "✗ SELECT failed: " . $e->getMessage() . "<br>";
}

// Test 5: Test UPDATE operation
echo "<h3>Test 5: Test UPDATE Operation</h3>";
try {
    $new_balance = 100.50;
    $stmt = $pdo->prepare('UPDATE ch_users SET balance = :balance WHERE email = :email');
    $stmt->execute([
        ':balance' => $new_balance,
        ':email' => $test_email
    ]);
    echo "✓ UPDATE successful<br>";
    
    // Verify the update
    $stmt = $pdo->prepare('SELECT balance FROM ch_users WHERE email = :email');
    $stmt->execute([':email' => $test_email]);
    $result = $stmt->fetch();
    echo "Updated balance: " . $result['balance'] . "<br>";
} catch (Exception $e) {
    echo "✗ UPDATE failed: " . $e->getMessage() . "<br>";
}

// Test 6: Check OTP table operations
echo "<h3>Test 6: Test OTP Table Operations</h3>";
try {
    // Insert OTP
    $stmt = $pdo->prepare('INSERT INTO ch_otps (target, otp_code, purpose, created_at, expires_at) VALUES (:target, :otp_code, :purpose, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
    $stmt->execute([
        ':target' => $test_email,
        ':otp_code' => '123456',
        ':purpose' => 'test',
    ]);
    echo "✓ OTP INSERT successful<br>";
    
    // Read OTP
    $stmt = $pdo->prepare('SELECT * FROM ch_otps WHERE target = :target LIMIT 1');
    $stmt->execute([':target' => $test_email]);
    $otp = $stmt->fetch();
    
    if ($otp) {
        echo "✓ OTP SELECT successful<br>";
        echo "OTP Code: " . $otp['otp_code'] . "<br>";
        echo "Expires: " . $otp['expires_at'] . "<br>";
    }
} catch (Exception $e) {
    echo "✗ OTP operation failed: " . $e->getMessage() . "<br>";
}

// Test 7: Check table list
echo "<h3>Test 7: Available Tables</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    echo "Total tables: " . count($tables) . "<br>";
    echo "Tables:<br>";
    foreach ($tables as $table) {
        $name = $table[0];
        echo "  - $name<br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 8: Cleanup test data
echo "<h3>Test 8: Cleanup</h3>";
try {
    $stmt = $pdo->prepare('DELETE FROM ch_users WHERE email = :email');
    $stmt->execute([':email' => $test_email]);
    echo "✓ Test data cleaned up<br>";
} catch (Exception $e) {
    echo "✗ Cleanup failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Home</a></p>";
?>
