<?php
// Enable error display
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/otp.php';

// Test OTP Manager
echo "<h2>OTP System Test</h2>";

// Check if otp_data directory exists
$otpDir = __DIR__ . '/otp_data';
if (!is_dir($otpDir)) {
    echo "<p style='color:red;'>Creating otp_data directory...</p>";
    mkdir($otpDir, 0755, true);
}
echo "<p style='color:green;'>✓ otp_data directory exists</p>";

// Test OTP generation
$testEmail = 'test@example.com';
$testPhone = '1234567890';

echo "<h3>Testing OTP Generation</h3>";
$otp = $otpManager->generateOTP();
echo "<p>Generated OTP: <strong>$otp</strong></p>";

echo "<h3>Testing OTP Storage</h3>";
$otpManager->storeOTP($testEmail, $otp, 'test');
echo "<p style='color:green;'>✓ OTP stored successfully</p>";

echo "<h3>Testing OTP Retrieval</h3>";
$otpData = $otpManager->getOTPData($testEmail);
if ($otpData) {
    echo "<pre>";
    print_r($otpData);
    echo "</pre>";
} else {
    echo "<p style='color:red;'>✗ Failed to retrieve OTP data</p>";
}

echo "<h3>Testing OTP Verification</h3>";
$result = $otpManager->verifyOTP($testEmail, $otp);
echo "<pre>";
print_r($result);
echo "</pre>";

echo "<h3>Log Files</h3>";
$logFile = $otpDir . '/otp_log.txt';
if (file_exists($logFile)) {
    echo "<h4>otp_log.txt:</h4>";
    echo "<pre>";
    echo htmlspecialchars(file_get_contents($logFile));
    echo "</pre>";
} else {
    echo "<p>No otp_log.txt file yet</p>";
}

echo "<h3>Testing Mail Function</h3>";
$to = 'test@example.com';
$subject = 'CryptoHub Test Email';
$body = 'This is a test email from CryptoHub';
$headers = "From: noreply@cryptohub.com\r\n";

$mailResult = @mail($to, $subject, $body, $headers);
if ($mailResult) {
    echo "<p style='color:green;'>✓ Mail function returned true (email queued)</p>";
} else {
    echo "<p style='color:red;'>✗ Mail function returned false</p>";
}

?>
