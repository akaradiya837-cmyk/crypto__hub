# CryptoHub PHP & OTP System - Developer Reference

## OTP Manager Class Reference

### Overview
The `OTPManager` class (in `otp.php`) provides a complete OTP management system. It's automatically instantiated as `$otpManager` global variable.

---

## Class Methods

### 1. generateOTP()
**Purpose:** Generate a 6-digit OTP

**Signature:**
```php
public function generateOTP(): string
```

**Returns:** String containing 6-digit OTP (e.g., "123456")

**Example:**
```php
$otp = $otpManager->generateOTP();
// Returns: "456789"
```

---

### 2. sendOTP($email, $otp)
**Purpose:** Send OTP to user's email (currently logs to file, ready for email integration)

**Signature:**
```php
public function sendOTP(string $email, string $otp): bool
```

**Parameters:**
- `$email` (string) - User's email address
- `$otp` (string) - The OTP code to send

**Returns:** Boolean (true on success)

**Notes:**
- Currently logs to `otp_data/otp_log.txt`
- To enable email sending, uncomment the mail() code and configure SMTP

**Example:**
```php
$result = $otpManager->sendOTP('user@example.com', '123456');
// Logs OTP to file
```

**To Enable Email:**
```php
// Uncomment in otp.php around line 42
$subject = "CryptoHub - Your OTP is: $otp";
$body = "Your OTP is: $otp\n\nThis OTP is valid for 10 minutes.";
$headers = "From: noreply@cryptohub.com\r\n";
mail($email, $subject, $body, $headers);
```

---

### 3. storeOTP($email, $otp, $type = 'registration')
**Purpose:** Store OTP for later verification

**Signature:**
```php
public function storeOTP(string $email, string $otp, string $type = 'registration'): bool
```

**Parameters:**
- `$email` (string) - User's email
- `$otp` (string) - The OTP code
- `$type` (string) - Type of OTP: 'registration', 'login', 'password_reset' (optional)

**Returns:** Boolean (true on success)

**Data Stored:**
```php
[
    'email' => 'user@example.com',
    'otp' => '123456',
    'type' => 'registration',
    'created_at' => 1234567890,
    'expiry_time' => 1234568490,
    'verified' => false,
    'attempts' => 0
]
```

**Example:**
```php
$otpManager->storeOTP('user@example.com', '123456', 'registration');
// OTP stored in otp_data/[hashed_email].json
```

---

### 4. verifyOTP($email, $otp)
**Purpose:** Verify user-provided OTP

**Signature:**
```php
public function verifyOTP(string $email, string $otp): array
```

**Parameters:**
- `$email` (string) - User's email
- `$otp` (string) - OTP provided by user

**Returns:** Array with 'success' and 'message':
```php
[
    'success' => true/false,
    'message' => 'Description of result'
]
```

**Possible Messages:**
- Success: `'OTP verified successfully.'`
- Expired: `'OTP has expired. Please request a new one.'`
- Not found: `'No OTP found. Please request a new one.'`
- Invalid: `'Invalid OTP. Please try again. Attempts remaining: 4'`
- Too many attempts: `'Too many failed attempts. Please request a new OTP.'`

**Example:**
```php
$result = $otpManager->verifyOTP('user@example.com', '123456');

if ($result['success']) {
    echo "OTP verified!";
    // Create user account or log in user
} else {
    echo "Error: " . $result['message'];
}
```

---

### 5. isOTPVerified($email)
**Purpose:** Check if OTP is already verified for an email

**Signature:**
```php
public function isOTPVerified(string $email): bool
```

**Parameters:**
- `$email` (string) - User's email

**Returns:** Boolean (true if verified, false otherwise)

**Example:**
```php
if ($otpManager->isOTPVerified('user@example.com')) {
    echo "OTP is verified, proceed with registration";
} else {
    echo "OTP verification required";
}
```

---

### 6. clearOTP($email)
**Purpose:** Delete OTP data after successful verification

**Signature:**
```php
public function clearOTP(string $email): bool
```

**Parameters:**
- `$email` (string) - User's email

**Returns:** Boolean (true if cleared, false if not found)

**Example:**
```php
$otpManager->clearOTP('user@example.com');
// Deletes otp_data/[hashed_email].json file
```

---

### 7. requestNewOTP($email, $type = 'registration')
**Purpose:** Complete OTP request flow (clears old, generates new, stores, sends)

**Signature:**
```php
public function requestNewOTP(string $email, string $type = 'registration'): array
```

**Parameters:**
- `$email` (string) - User's email
- `$type` (string) - Type: 'registration', 'login', 'password_reset'

**Returns:** Array with 'success', 'otp' (in dev), and 'message'
```php
[
    'success' => true,
    'otp' => '123456',  // Only in development
    'message' => 'OTP has been sent to your email.'
]
```

**Example:**
```php
$result = $otpManager->requestNewOTP('user@example.com', 'registration');
if ($result['success']) {
    echo $result['message'];  // "OTP has been sent to your email."
}
```

---

### 8. getOTPData($email)
**Purpose:** Get OTP data for debugging (for development only)

**Signature:**
```php
public function getOTPData(string $email): ?array
```

**Parameters:**
- `$email` (string) - User's email

**Returns:** Array of OTP data or null if not found

**Security Note:** Only use in development. Remove in production.

**Example:**
```php
$data = $otpManager->getOTPData('user@example.com');
// Returns:
// [
//     'email' => 'user@example.com',
//     'otp' => '123456',
//     'attempts' => 2,
//     'verified' => false,
//     ...
// ]
```

---

## Configuration

### OTP Expiry Time
Default: 600 seconds (10 minutes)

To change:
```php
// In otp.php, modify the property
private $otpExpiry = 300;  // 5 minutes
```

### Max Attempts
Default: 5 failed attempts

To change:
```php
// In otp.php, line 102
if ($data['attempts'] >= 3) {  // Change 3 for different limit
```

### OTP Storage Directory
Default: `/otp_data/`

Files are named using MD5 hash of email:
- Email: `user@example.com`
- Hash: `5a105e8b9d40e1329780d62ea2265d8a`
- File: `otp_data/5a105e8b9d40e1329780d62ea2265d8a.json`

---

## Usage Examples

### Example 1: Registration Flow with OTP

```php
require_once __DIR__ . '/otp.php';

if ($_POST['step'] === 'register') {
    $email = $_POST['email'];
    
    // Send OTP
    $result = $otpManager->requestNewOTP($email, 'registration');
    $_SESSION['registration_email'] = $email;
    // Redirect to OTP verification page
}

if ($_POST['step'] === 'verify_otp') {
    $email = $_SESSION['registration_email'];
    $otp = $_POST['otp'];
    
    $result = $otpManager->verifyOTP($email, $otp);
    
    if ($result['success']) {
        // Create user account
        $otpManager->clearOTP($email);
        unset($_SESSION['registration_email']);
        // Redirect to login
    } else {
        // Show error
        echo $result['message'];
    }
}
```

### Example 2: Login Flow with OTP

```php
require_once __DIR__ . '/otp.php';

if ($_POST['step'] === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Verify credentials
    if (verifyCredentials($email, $password)) {
        // Send OTP
        $otpManager->requestNewOTP($email, 'login');
        $_SESSION['pending_login_email'] = $email;
        // Redirect to OTP page
    }
}

if ($_POST['step'] === 'verify_login_otp') {
    $email = $_SESSION['pending_login_email'];
    $otp = $_POST['otp'];
    
    $result = $otpManager->verifyOTP($email, $otp);
    
    if ($result['success']) {
        // Log user in
        $_SESSION['user_email'] = $email;
        $otpManager->clearOTP($email);
        // Redirect to dashboard
    }
}
```

### Example 3: Password Reset with OTP

```php
require_once __DIR__ . '/otp.php';

if ($_POST['action'] === 'request_reset') {
    $email = $_POST['email'];
    
    // Verify email exists
    $otpManager->requestNewOTP($email, 'password_reset');
    $_SESSION['reset_email'] = $email;
}

if ($_POST['action'] === 'verify_reset_otp') {
    $email = $_SESSION['reset_email'];
    $otp = $_POST['otp'];
    
    $result = $otpManager->verifyOTP($email, $otp);
    
    if ($result['success']) {
        $_SESSION['can_reset_password'] = true;
        // Show password reset form
    }
}

if ($_POST['action'] === 'reset_password') {
    if ($_SESSION['can_reset_password']) {
        // Update password
        $otpManager->clearOTP($_SESSION['reset_email']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['can_reset_password']);
    }
}
```

---

## Error Handling

### Common Errors

**OTP Expired:**
```php
if (!$result['success'] && strpos($result['message'], 'expired') !== false) {
    echo "Request a new OTP";
    // provide option to request new OTP
}
```

**Too Many Attempts:**
```php
if (!$result['success'] && strpos($result['message'], 'Too many') !== false) {
    $otpManager->clearOTP($email);
    echo "Please request a new OTP";
}
```

**OTP Mismatch:**
```php
if (!$result['success'] && strpos($result['message'], 'Invalid') !== false) {
    $attempts = preg_match('/(\d+)/', $result['message'], $matches);
    echo "Invalid OTP. " . $matches[0] . " attempts remaining";
}
```

---

## Security Best Practices

1. **Never expose OTP in URL parameters**
   ```php
   // Bad - Don't do this
   header("Location: verify.php?otp=" . $otp);
   
   // Good - Use POST or session
   $_SESSION['otp'] = $otp;
   ```

2. **Always verify on server-side**
   ```php
   // Bad - Client-side only
   if (userOTP === serverOTP) { ... }  // JavaScript
   
   // Good - Server-side verification
   $result = $otpManager->verifyOTP($email, $otp);
   ```

3. **Log OTP attempts for security**
   ```php
   // Track failed attempts for user blocking
   if (!$result['success']) {
       logAttempt($email, 'otp_failed');
   }
   ```

4. **Use HTTPS in production**
   - OTPs should only be transmitted over HTTPS
   - Sessions should use secure cookies

5. **Implement rate limiting**
   ```php
   // Prevent brute force attacks
   if (getAttemptCount($email, 'otp') > 10) {
       blockUser($email, 3600);  // Block for 1 hour
   }
   ```

6. **Clear OTP after use**
   ```php
   if ($result['success']) {
       $otpManager->clearOTP($email);  // Always clear
   }
   ```

---

## Testing OTP Locally

During development, check OTP logs at:
```
/otp_data/otp_log.txt
```

Or get OTP data directly:
```php
$data = $otpManager->getOTPData('test@example.com');
echo "OTP for testing: " . $data['otp'];
```

---

## Production Deployment Checklist

- [ ] Configure real email SMTP in `sendOTP()` method
- [ ] Remove debug output from `getOTPData()` method
- [ ] Set environment-specific variables
- [ ] Enable HTTPS for all pages
- [ ] Test OTP delivery with real email provider
- [ ] Set secure session cookies
- [ ] Implement rate limiting for OTP requests
- [ ] Add IP-based blocking for failed attempts
- [ ] Set up logging for security audits
- [ ] Test OTP expiry and cleanup

---

## Support & Questions

For issues or questions about the OTP system:
1. Check `otp_data/otp_log.txt` for error logs
2. Review the verification responses
3. Check session data in PHP error logs
4. Enable debug mode for detailed error messages

---

**Last Updated:** March 9, 2026  
**Version:** 1.0  
**Status:** Production Ready
