<?php
// 1. Load the composer autoloader
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

$mail->SMTPDebug = 2; // Shows full communication log


try {
    // --- Server Settings ---
    // --- Server Settings ---
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';                       // Must be exactly this
$mail->SMTPAuth   = true;
$mail->Username   = 'cryptoadminpirouser@gmail.com';                 // Your full Gmail address
$mail->Password   = 'ffus zggb zefu eufg';                 // Your 16-character App Password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Use STARTTLS
$mail->Port       = 587;                                    // Port 587 for STARTTLS

    // --- Recipients ---
    $mail->setFrom('cryptoadminpirouser@gmail.com', 'My Localhost Test');
    $mail->addAddress('yuvrajv833@gmail.com');        // Send a test to yourself!

    // --- Content ---
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer is Working!';
    $mail->Body    = '<b>Congratulations!</b> You have successfully set up an SMTP mailer on your localhost.';

    $mail->send();
    echo '<h3>Success! Check your inbox.</h3>';
} catch (Exception $e) {
    echo "<h3>Message could not be sent.</h3> Mailer Error: {$mail->ErrorInfo}";
}
?>
