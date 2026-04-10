<?php
/**
 * OTP Verification Diagnostic Test
 * This script helps diagnose OTP registration issues
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/otp_manager.php';
require_once __DIR__ . '/otp_mailer.php';

$test_email = 'testuser@example.com';
$test_purpose = 'registration';

echo "<h2>OTP Verification Diagnostic Test</h2>";
echo "<hr>";

// Test 1: Generate and Store OTP
echo "<h3>Test 1: Generate and Store OTP</h3>";
$otpManager = new OTPManager();
$generated_otp = $otpManager->storeOTP($test_email, $test_purpose);
echo "Generated OTP: <strong>$generated_otp</strong><br>";

// Test 2: Check Database Connection
echo "<h3>Test 2: Database Connection</h3>";
$pdo = getDB();
if ($pdo) {
    echo "✓ Database connected successfully<br>";
} else {
    echo "✗ Database connection failed<br>";
}

// Test 3: Verify OTP is stored in database
echo "<h3>Test 3: Check OTP in Database</h3>";
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM ch_otps WHERE target = :target ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([':target' => $test_email]);
        $otp_record = $stmt->fetch();
        
        if ($otp_record) {
            echo "<pre>";
            print_r($otp_record);
            echo "</pre>";
            echo "Created at: " . $otp_record['created_at'] . "<br>";
            echo "Expires at: " . $otp_record['expires_at'] . "<br>";
            echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
            
            // Check if expired
            if (strtotime($otp_record['expires_at']) > time()) {
                echo "✓ OTP is NOT expired<br>";
            } else {
                echo "✗ OTP is EXPIRED<br>";
            }
        } else {
            echo "✗ OTP not found in database<br>";
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "<br>";
    }
}

// Test 4: Test OTP Verification
echo "<h3>Test 4: Test OTP Verification</h3>";
if ($generated_otp) {
    $verify_result = $otpManager->verifyOTP($test_email, $generated_otp, $test_purpose);
    if ($verify_result) {
        echo "✓ OTP verification successful!<br>";
    } else {
        echo "✗ OTP verification failed<br>";
    }
}

// Test 5: Check system timezone
echo "<h3>Test 5: System Timezone</h3>";
echo "PHP Timezone: " . date_default_timezone_get() . "<br>";

// Test 6: Check MySQL timezone (if connected)
if ($pdo) {
    try {
        $stmt = $pdo->query('SELECT NOW() as db_time, @@global.time_zone as tz, @@session.time_zone as session_tz');
        $result = $stmt->fetch();
        echo "Database time: " . $result['db_time'] . "<br>";
        echo "Global timezone: " . $result['tz'] . "<br>";
        echo "Session timezone: " . $result['session_tz'] . "<br>";
    } catch (Exception $e) {
        echo "Could not retrieve MySQL timezone: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";
echo "<p><a href='register.php'>Back to Registration</a></p>";
?>
