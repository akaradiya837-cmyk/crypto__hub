<?php
/**
 * OTP Registration Test Script
 * Tests the complete OTP registration flow
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/otp_manager.php';

$test_email = 'debug@test.com';
$pdo = getDB();

echo "<h2>OTP Registration Diagnostic</h2>";
echo "<hr>";

// Test 1: Check database connection
echo "<h3>Test 1: Database Connection</h3>";
if ($pdo) {
    echo "✓ Connected to database<br>";
} else {
    echo "✗ Failed to connect to database<br>";
    die("Cannot continue without database");
}

// Test 2: Check timezone settings
echo "<h3>Test 2: Timezone Settings</h3>";
echo "PHP Timezone: " . date_default_timezone_get() . "<br>";
echo "PHP Current Time: " . date('Y-m-d H:i:s') . "<br>";

// Get MySQL timezone and time
try {
    $stmt = $pdo->query('SELECT NOW() as mysql_time, @@global.time_zone as global_tz, @@session.time_zone as session_tz');
    $result = $stmt->fetch();
    echo "MySQL Time: " . $result['mysql_time'] . "<br>";
    echo "MySQL Global TZ: " . $result['global_tz'] . "<br>";
    echo "MySQL Session TZ: " . $result['session_tz'] . "<br>";
} catch (Exception $e) {
    echo "Error getting MySQL time: " . $e->getMessage() . "<br>";
}

// Test 3: Create OTP
echo "<h3>Test 3: Create and Store OTP</h3>";
$otpManager = new OTPManager();

// Delete any existing test OTPs
try {
    $stmt = $pdo->prepare('DELETE FROM ch_otps WHERE target = :target');
    $stmt->execute([':target' => $test_email]);
    echo "Cleaned up existing OTPs<br>";
} catch (Exception $e) {
    echo "Error cleaning up: " . $e->getMessage() . "<br>";
}

$otp_code = $otpManager->storeOTP($test_email, 'registration');
if ($otp_code) {
    echo "✓ OTP Generated: <strong>$otp_code</strong><br>";
} else {
    echo "✗ Failed to generate OTP<br>";
    die("Cannot continue");
}

// Test 4: Check database record
echo "<h3>Test 4: Verify OTP in Database</h3>";
try {
    $stmt = $pdo->prepare('SELECT id, target, otp_code, purpose, created_at, expires_at FROM ch_otps WHERE target = :target LIMIT 1');
    $stmt->execute([':target' => $test_email]);
    $record = $stmt->fetch();
    
    if ($record) {
        echo "Found in database:<br>";
        echo "  OTP Code: " . $record['otp_code'] . "<br>";
        echo "  Purpose: " . $record['purpose'] . "<br>";
        echo "  Created: " . $record['created_at'] . "<br>";
        echo "  Expires: " . $record['expires_at'] . "<br>";
        
        // Check expiration
        $now = new DateTime();
        $expires = new DateTime($record['expires_at']);
        
        if ($expires > $now) {
            echo "  Status: ✓ NOT EXPIRED<br>";
        } else {
            echo "  Status: ✗ EXPIRED<br>";
        }
    } else {
        echo "✗ OTP not found in database<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 5: Verify OTP with wrong code
echo "<h3>Test 5: Test Verification with Wrong Code</h3>";
$wrong_otp = '000000';
$result = $otpManager->verifyOTP($test_email, $wrong_otp, 'registration');
if ($result) {
    echo "✗ Wrong OTP verified (BUG!)";
} else {
    echo "✓ Wrong OTP correctly rejected<br>";
}

// Test 6: Verify OTP with correct code
echo "<h3>Test 6: Test Verification with Correct Code</h3>";
$result = $otpManager->verifyOTP($test_email, $otp_code, 'registration');
if ($result) {
    echo "✓ Correct OTP verified successfully<br>";
} else {
    echo "✗ Correct OTP verification failed (BUG!)";
}

// Test 7: Check if OTP was deleted after verification
echo "<h3>Test 7: Check OTP After Verification</h3>";
try {
    $stmt = $pdo->prepare('SELECT id FROM ch_otps WHERE target = :target LIMIT 1');
    $stmt->execute([':target' => $test_email]);
    $record = $stmt->fetch();
    
    if ($record) {
        echo "✗ OTP still in database (should be deleted!) <br>";
    } else {
        echo "✓ OTP properly deleted after verification<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='register.php'>Back to Registration</a> | <a href='test-otp-verification.php'>Run Full Diagnostic</a></p>";
?>
