<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set header to JSON for the JavaScript fetch request
header('Content-Type: application/json');

// Rate limiting: Prevent sending multiple OTPs too quickly
if (isset($_SESSION['last_otp_time']) && time() - $_SESSION['last_otp_time'] < 60) {
    // Return JSON response for rate limit error
    echo json_encode(['success' => false, 'message' => 'Please wait 60 seconds before requesting a new OTP.']);
    exit();
}

// Check if email is provided in POST request
if (!isset($_POST['email']) || empty(trim($_POST['email']))) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit();
}

$email = trim($_POST['email']); // Trim email here as well for consistency

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
     echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
     exit();
}

// Check if the email exists in the database
$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    error_log("Database Connection failed in send_otp_phpmailer: " . $db->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}
$db->set_charset("utf8mb4");

$stmt_check_email = $db->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt_check_email) {
    error_log("Send OTP Prepare failed (email check): " . $db->error);
    echo json_encode(['success' => false, 'message' => 'Error preparing email check.']);
    $db->close();
    exit();
}
$stmt_check_email->bind_param("s", $email);
$stmt_check_email->execute();
$stmt_check_email->store_result();

if ($stmt_check_email->num_rows === 0) {
    // Email does not exist in the database
    echo json_encode(['success' => false, 'message' => 'Email address not found.']);
    $stmt_check_email->close();
    $db->close();
    exit();
}

$stmt_check_email->close();
$db->close();


// Generate OTP
$otp = rand(100000, 999999);

// Save OTP and related info to session
$_SESSION['otp'] = $otp;
$_SESSION['otp_email'] = $email; // Store the trimmed, validated email
$_SESSION['otp_expires'] = time() + 300; // 5 minutes expiry
$_SESSION['last_otp_time'] = time(); // Record time of sending


$mail = new PHPMailer(true);

try {
    // Server settings (ensure these are correct for your SMTP provider)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // e.g., 'smtp.gmail.com' for Gmail
    $mail->SMTPAuth   = true;
    $mail->Username   = 'fouzi.slimani75@gmail.com';        // Your Gmail
    $mail->Password   = 'lzodohaionsmcurg';          // App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use ENCRYPTION_SMTPS for port 465
    $mail->Port       = 587; // TCP port to connect to; use 465 for `SMTPS`

    // Recipients
    $mail->setFrom('fouzi.slimani75@gmail.com', 'SefarGifts'); // Your sender email and name
    $mail->addAddress($email); // Add recipient

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Code for SefarGifts';
    $mail->Body    = '<p>Your One-Time Password (OTP) code is: <strong>' . $otp . '</strong></p><p>This code is valid for 5 minutes.</p><p>If you did not request this code, please ignore this email.</p>';
    $mail->AltBody = 'Your One-Time Password (OTP) code is: ' . $otp . '. This code is valid for 5 minutes.'; // Plain text for non-HTML clients

    $mail->send();

    // Return JSON success response
    echo json_encode(['success' => true, 'message' => 'OTP has been sent to your email.']);

} catch (Exception $e) {
    // Log the PHPMailer error
    error_log("PHPMailer Error in send_otp_phpmailer: {$mail->ErrorInfo}");
    // Return JSON error response
    echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again later.']);
}
?>
