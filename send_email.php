<?php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = '://gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'cryptoadminpirouser@gmail.com';     // Your Gmail address
    $mail->Password   = 'ffus zggb zefu eufg';        // 16-character App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('cryptoadminpirouser@gmail.com', 'Mailer Test');
    $mail->addAddress('recipient@example.com');     // Where you want the mail to go

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test from Localhost';
    $mail->Body    = '<b>Success!</b> PHPMailer is working on your Laragon setup.';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
