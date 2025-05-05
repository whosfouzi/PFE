<?php
session_start();
header('Content-Type: text/plain');

if (
    !isset($_POST['email']) ||
    !isset($_POST['otp']) ||
    !isset($_POST['new_password']) ||
    !isset($_POST['confirm_password'])
) {
    http_response_code(400);
    echo "All fields are required.";
    exit();
}

$email = $_POST['email'];
$otp = $_POST['otp'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Validate OTP
if (!isset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expires'])) {
    http_response_code(403);
    echo "No OTP session found.";
    exit();
}

if (time() > $_SESSION['otp_expires']) {
    unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expires']);
    http_response_code(403);
    echo "OTP expired.";
    exit();
}

if ($_SESSION['otp_email'] !== $email || $_SESSION['otp'] != $otp) {
    http_response_code(403);
    echo "Invalid OTP or email.";
    exit();
}

if ($new_password !== $confirm_password) {
    http_response_code(400);
    echo "Passwords do not match.";
    exit();
}

// Hash password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update database
$db = new mysqli("localhost", "root", "", "giftstore");

if ($db->connect_error) {
    http_response_code(500);
    echo "Database connection failed.";
    exit();
}

$stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {
    echo "Password updated successfully.";
    unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expires']);
} else {
    http_response_code(500);
    echo "Failed to update password.";
}

$stmt->close();
$db->close();
?>
