<?php
session_start();
// No longer outputting plain text directly, will redirect with messages

// Check if all required fields are set in the POST request
if (
    !isset($_POST['email']) ||
    !isset($_POST['otp']) ||
    !isset($_POST['new_password']) ||
    !isset($_POST['confirm_password'])
) {
    $_SESSION['password_change_error'] = 'All fields are required.';
    header('Location: my_account.php#security'); // Redirect back to security section
    exit();
}

$email = trim($_POST['email']); // Trim email here
$otp = $_POST['otp'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Validate OTP session data exists
if (!isset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expires'])) {
    $_SESSION['password_change_error'] = 'No OTP session found. Please request a new OTP.';
    error_log("Verify Password Error: No OTP session found for email: $email");
    header('Location: my_account.php#security');
    exit();
}

// Validate OTP expiry
if (time() > $_SESSION['otp_expires']) {
    unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expires']); // Clear expired OTP
    $_SESSION['password_change_error'] = 'OTP expired. Please request a new OTP.';
    error_log("Verify Password Error: OTP expired for email: $email");
    header('Location: my_account.php#security');
    exit();
}

// Validate submitted email and OTP against session data
// Use strict comparison for email after trimming
// Use loose comparison for OTP as session stores int, POST sends string
if ($_SESSION['otp_email'] !== $email || $_SESSION['otp'] != $otp) {
    $_SESSION['password_change_error'] = 'Invalid OTP or email.';
     error_log("Verify Password Error: Invalid OTP/email. Session Email: " . $_SESSION['otp_email'] . ", Submitted Email: $email. Session OTP: " . $_SESSION['otp'] . ", Submitted OTP: $otp");
    header('Location: my_account.php#security');
    exit();
}

// Validate new password and confirmation
if ($new_password !== $confirm_password) {
    $_SESSION['password_change_error'] = 'New passwords do not match.';
     error_log("Verify Password Error: Passwords do not match for email: $email");
    header('Location: my_account.php#security');
    exit();
}

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update database
$db = new mysqli("localhost", "root", "", "giftstore");

if ($db->connect_error) {
    error_log("Database Connection failed in verify_change_password: " . $db->connect_error);
    $_SESSION['password_change_error'] = 'Database connection failed.';
    header('Location: my_account.php#security');
    exit();
}
$db->set_charset("utf8mb4");


$stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
if (!$stmt) {
    error_log("Verify Password Prepare failed: " . $db->error);
    $_SESSION['password_change_error'] = 'Error preparing password update query.';
     $db->close();
    header('Location: my_account.php#security');
    exit();
}
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {
    // Password updated successfully
    $_SESSION['password_change_success'] = 'Password updated successfully.';
    // Clear the OTP session data after successful verification
    unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expires']);
     error_log("Verify Password Info: Password updated successfully for email: $email");

} else {
    // Failed to update password
    error_log("Verify Password Execute failed: " . $stmt->error);
    $_SESSION['password_change_error'] = 'Failed to update password.';
}

$stmt->close();
$db->close();

// Redirect back to the security section of my_account.php
header('Location: my_account.php#security');
exit();
?>
