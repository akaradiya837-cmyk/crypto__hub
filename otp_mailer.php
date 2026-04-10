<?php
/**
 * OTP Mailer Class - Sends OTPs via Gmail SMTP using PHPMailer
 */
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class OTPMailer {
    private $sender_email = 'cryptoadminpirouser@gmail.com';
    private $sender_name = 'CryptoHub';
    private $sender_password = 'ffus zggb zefu eufg';
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    
    /**
     * Send OTP via email
     * Returns true on success, false on failure
     */
    public function sendOTP($recipient_email, $otp_code, $purpose = 'verification') {
        try {
            $mail = new PHPMailer(true);
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->sender_email;
            $mail->Password = $this->sender_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            
            // Sender
            $mail->setFrom($this->sender_email, $this->sender_name);
            $mail->addAddress($recipient_email);
            
            // Email Content
            $mail->isHTML(true);
            
            // Customize subject and body based on purpose
            switch ($purpose) {
                case 'registration':
                    $mail->Subject = 'CryptoHub - Email Verification OTP';
                    $body = $this->getRegistrationEmailBody($otp_code);
                    break;
                case 'password_change':
                    $mail->Subject = 'CryptoHub - Password Change Verification';
                    $body = $this->getPasswordChangeEmailBody($otp_code);
                    break;
                case 'password_reset':
                    $mail->Subject = 'CryptoHub - Password Reset Request';
                    $body = $this->getPasswordResetEmailBody($otp_code);
                    break;
                default:
                    $mail->Subject = 'CryptoHub - Verification Code';
                    $body = $this->getDefaultEmailBody($otp_code);
            }
            
            $mail->Body = $body;
            $mail->AltBody = "Your OTP code is: $otp_code";
            
            // Send
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("OTP Mail Error: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Email body for registration verification
     */
    private function getRegistrationEmailBody($otp_code) {
        return "
        <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 10px;'>
                    <h2 style='color: #667eea;'>Welcome to CryptoHub! 🚀</h2>
                    
                    <p>Thank you for registering with us. To complete your sign-up, please verify your email address.</p>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>
                        <p style='color: #666; margin-bottom: 10px;'>Your verification code is:</p>
                        <h1 style='color: #667eea; font-size: 48px; letter-spacing: 5px; margin: 10px 0;'>$otp_code</h1>
                        <p style='color: #999; font-size: 14px;'>This code expires in 10 minutes</p>
                    </div>
                    
                    <p style='color: #666;'>If you didn't request this verification code, please ignore this email.</p>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #999; text-align: center;'>
                        CryptoHub © 2026. All rights reserved.
                    </p>
                </div>
            </body>
        </html>";
    }
    
    /**
     * Email body for password change verification
     */
    private function getPasswordChangeEmailBody($otp_code) {
        return "
        <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 10px;'>
                    <h2 style='color: #667eea;'>Password Change Verification</h2>
                    
                    <p>You've requested to change your CryptoHub account password.</p>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>
                        <p style='color: #666; margin-bottom: 10px;'>Use this code to confirm your password change:</p>
                        <h1 style='color: #667eea; font-size: 48px; letter-spacing: 5px; margin: 10px 0;'>$otp_code</h1>
                        <p style='color: #999; font-size: 14px;'>This code expires in 10 minutes</p>
                    </div>
                    
                    <p style='color: #d9534f; font-weight: bold;'>⚠️ If you didn't request this, your account may be compromised. Please contact support immediately.</p>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #999; text-align: center;'>
                        CryptoHub © 2026. All rights reserved.
                    </p>
                </div>
            </body>
        </html>";
    }
    
    /**
     * Email body for password reset
     */
    private function getPasswordResetEmailBody($otp_code) {
        return "
        <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 10px;'>
                    <h2 style='color: #667eea;'>Password Reset Request</h2>
                    
                    <p>You've requested to reset your CryptoHub account password.</p>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>
                        <p style='color: #666; margin-bottom: 10px;'>Use this code to reset your password:</p>
                        <h1 style='color: #667eea; font-size: 48px; letter-spacing: 5px; margin: 10px 0;'>$otp_code</h1>
                        <p style='color: #999; font-size: 14px;'>This code expires in 10 minutes</p>
                    </div>
                    
                    <p style='color: #666;'>If you didn't request a password reset, please ignore this email.</p>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #999; text-align: center;'>
                        CryptoHub © 2026. All rights reserved.
                    </p>
                </div>
            </body>
        </html>";
    }
    
    /**
     * Default email body
     */
    private function getDefaultEmailBody($otp_code) {
        return "
        <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 10px;'>
                    <h2 style='color: #667eea;'>CryptoHub Verification</h2>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>
                        <p style='color: #666; margin-bottom: 10px;'>Your verification code is:</p>
                        <h1 style='color: #667eea; font-size: 48px; letter-spacing: 5px; margin: 10px 0;'>$otp_code</h1>
                        <p style='color: #999; font-size: 14px;'>This code expires in 10 minutes</p>
                    </div>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #999; text-align: center;'>
                        CryptoHub © 2026. All rights reserved.
                    </p>
                </div>
            </body>
        </html>";
    }
}
?>
