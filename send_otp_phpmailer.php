<?php
session_start();
require __DIR__ . '/vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: text/plain");

if (!isset($_POST['email'])) {
    http_response_code(400);
    echo "Email is required.";
    exit();
}

$email = $_POST['email'];  // this must be BEFORE using $email

$otp = rand(100000, 999999);

// Save OTP to session
$_SESSION['otp'] = $otp;
$_SESSION['otp_email'] = $email;
$_SESSION['otp_expires'] = time() + 300; // 5 minutes

$mail = new PHPMailer(true);

try {
    $mail->addAddress($email);
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'fouzi.slimani75@gmail.com';        // Your Gmail
    $mail->Password   = 'lzodohaionsmcurg';          // App password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('fouzi.slimani75@gmail.com', 'SefarGifts');
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Code';
    $mail->Body    = '<p>Your OTP code is: <strong>' . $otp . '</strong><br>This code will expire in 5 minutes.</p>';

    $mail->send();
    echo "OTP has been sent to your email.";
} catch (Exception $e) {
    http_response_code(500);
    echo "Failed to send OTP. Mailer Error: {$mail->ErrorInfo}";
}
?>