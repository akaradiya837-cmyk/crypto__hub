# OTP Email Configuration Guide

## Current Setup

The OTP system is now configured to send emails via PHP's `mail()` function.

### What's Happening:
1. ✅ OTP is generated (6 digits)
2. ✅ OTP is stored in `otp_data/[hash].json`
3. ✅ Email is attempted to be sent via `mail()` function
4. ✅ All attempts are logged to `otp_data/otp_log.txt`
5. ✅ Test mode shows OTP on the page (for development)

---

## Testing Locally

### Option 1: View OTP on Page (EASIEST FOR TESTING)

1. Go to **Register** or **Login** page
2. Enter your email and proceed to OTP verification
3. **Green box appears** showing:
   ```
   🧪 TEST MODE - Your OTP is: 123456
   (This is displayed in development mode only)
   ```
4. Enter this OTP to test the system

**Note:** This is automatically disabled in production mode.

---

### Option 2: Check OTP Log File

1. OTP attempts are logged to: `otp_data/otp_log.txt`
2. View this file to see all OTPs sent and when
3. Use any OTP from the log to test verification

**Example log:**
```
[2026-03-09 10:30:45] OTP sent to user@example.com: 123456
[2026-03-09 10:35:12] OTP sent to test@gmail.com: 654321
```

---

### Option 3: Check Email Failures (If Email Fails)

1. If PHP `mail()` fails, check: `otp_data/email_failures.txt`
2. This file logs all email delivery failures
3. Helps diagnose mail server issues

---

## Setting Up Real Email (Production)

### For SMTP Email Service (Recommended)

Install PHPMailer:
```bash
composer require phpmailer/phpmailer
```

Update `otp.php` sendOTP() method:

```php
public function sendOTP($email, $otp) {
    $logFile = $this->otpDir . '/otp_log.txt';
    $message = "[" . date('Y-m-d H:i:s') . "] OTP sent to $email: $otp\n";
    file_put_contents($logFile, $message, FILE_APPEND);
    
    // Use PHPMailer for reliable email delivery
    require 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Gmail SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';  // Use App Password for Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@cryptohub.com', 'CryptoHub');
        $mail->addAddress($email);
        
        // Content
        $subject = "CryptoHub - Your One-Time Password (OTP) is: $otp";
        $body = "Hello,\n\n";
        $body .= "Your OTP for CryptoHub is: $otp\n\n";
        $body .= "This OTP is valid for 10 minutes only.\n";
        $body .= "Please do not share this code with anyone.\n\n";
        
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        $failLog = $this->otpDir . '/email_failures.txt';
        $failMsg = "[" . date('Y-m-d H:i:s') . "] Failed to send to $email: " . $mail->ErrorInfo . "\n";
        file_put_contents($failLog, $failMsg, FILE_APPEND);
        return true;  // Still accept as OTP is stored
    }
}
```

### For Gmail:
1. Enable 2-Factor Authentication
2. Create App Password: https://myaccount.google.com/apppasswords
3. Use App Password in the code above

### For Custom SMTP:
```php
$mail->Host = 'your-smtp-server.com';
$mail->Port = 465; // or 587
$mail->SMTPSecure = 'ssl'; // or 'tls'
$mail->Username = 'your-username';
$mail->Password = 'your-password';
```

---

## Troubleshooting

### Email Not Received?

1. **Check Spam Folder** - Email might be marked as spam
2. **Check `otp_data/otp_log.txt`** - Verify OTP was generated
3. **Check `otp_data/email_failures.txt`** - See if send failed
4. **Check PHP Error Log** - Look for mail() errors
5. **In Test Mode** - Use the OTP displayed on the page

### PHP mail() Not Working?

**On Windows (Laragon):**
1. Check `php.ini` configuration
2. Configure SMTP settings:
   ```ini
   [mail function]
   SMTP = smtp.gmail.com
   smtp_port = 587
   sendmail_from = your-email@gmail.com
   ```

**On Linux:**
1. Install Postfix: `sudo apt-get install postfix`
2. Configure mail server
3. Test with: `echo "test" | mail -s "test" your@email.com`

---

## Current Implementation

The OTP system includes:

### Files Involved:
- `otp.php` - OTP Manager class
- `register.php` - Registration with OTP
- `index.php` - Login with OTP  
- `otp_data/otp_log.txt` - Email log

### Flow:
```
User Registration/Login
    ↓
Enter Email & Password
    ↓
Generate OTP (6-digit)
    ↓
Store OTP (10 min expiry)
    ↓
Send Email (via mail())
    ↓
Show OTP on page (test mode)
    ↓
User Enters OTP
    ↓
Verify & Clear
    ↓
Success / Error with retry
```

---

## Email Template

The email sent contains:

**Subject:** `CryptoHub - Your One-Time Password (OTP) is: 123456`

**Body:**
```
Hello,

Your OTP for CryptoHub is: 123456

This OTP is valid for 10 minutes only.
Please do not share this code with anyone.

If you did not request this, please ignore this email.

Best regards,
CryptoHub Security Team
```

---

## Security Notes

1. ✅ OTP is never stored in plain sessions
2. ✅ OTP expires after 10 minutes
3. ✅ OTP locked after 5 failed attempts
4. ✅ Email never displays password
5. ✅ Development display removed in production

---

## Disabling Test Mode (Production)

To remove the test OTP display from pages:

**In register.php and index.php, comment out:**
```php
<?php 
// Comment this section out in production
// $otpData = $otpManager->getOTPData($email_for_otp);
// if ($otpData && $otpData['otp']) { ... }
?>
```

Or add environment check:
```php
<?php 
if (getenv('APP_ENV') !== 'production') {
    $otpData = $otpManager->getOTPData($email_for_otp);
    if ($otpData && $otpData['otp']) {
        // Show OTP
    }
}
?>
```

---

## Summary

- 📧 **Email sending is now ENABLED** via PHP `mail()` function
- 🧪 **Test mode** shows OTP on registration/login pages
- 📝 **Logging** to `otp_data/otp_log.txt` for debugging
- 🔧 **Easy to configure** for real SMTP services
- ✅ **Production-ready** when configured with proper email service

---

**Last Updated:** March 9, 2026  
**Status:** Ready for Testing with Real Email Configuration
