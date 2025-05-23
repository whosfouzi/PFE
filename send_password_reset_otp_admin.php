<?php
session_start();
header('Content-Type: application/json');

include('send_email.php'); // Ensure this file exists and is correctly configured

// Ensure only admin can access this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? ''; // This should be the admin's current email
    $admin_id = $_SESSION['id'];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit();
    }

    $db = new mysqli("localhost", "root", "", "giftstore");
    if ($db->connect_error) {
        error_log("Database Connection failed in send_password_reset_otp_admin.php: " . $db->connect_error);
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
        exit();
    }

    // Verify the email belongs to the current admin
    $stmt_check_email = $db->prepare("SELECT id FROM users WHERE email = ? AND id = ? AND role = 'admin'");
    if (!$stmt_check_email) {
        error_log("Prepare failed for email verification in send_password_reset_otp_admin.php: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Database error during email verification.']);
        $db->close();
        exit();
    }
    $stmt_check_email->bind_param("si", $email, $admin_id);
    $stmt_check_email->execute();
    $stmt_check_email->store_result();

    if ($stmt_check_email->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email address does not match your admin account.']);
        $stmt_check_email->close();
        $db->close();
        exit();
    }
    $stmt_check_email->close();
    $db->close();

    // Generate a 6-digit OTP
    $otp = rand(100000, 999999);
    // Set OTP expiry time (e.g., 10 minutes from now)
    $otp_expiry = time() + (10 * 60); // Current time + 10 minutes

    // Store OTP, its expiry, and the admin ID in the session for password change
    $_SESSION['admin_password_change_otp'] = $otp;
    $_SESSION['admin_password_change_otp_expiry'] = $otp_expiry;
    $_SESSION['admin_password_change_id'] = $admin_id;
    $_SESSION['admin_password_change_email_for_otp'] = $email; // Store email used for OTP to verify later

    // Send the OTP via email
    $subject = "Your Admin Password Reset Code for SefarGifts";
    $body = "Dear Admin,<br><br>You have requested to change your password on SefarGifts Admin Panel. Your One-Time Password (OTP) for verification is: <b>" . $otp . "</b>. This code is valid for 10 minutes.<br><br>If you did not request this password change, please ignore this email or contact support immediately.<br><br>Best regards,<br>The SefarGifts Admin Team";

    $mailSent = sendEmail($email, $subject, $body);

    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email! Check your inbox (and spam).']);
    } else {
        error_log("Failed to send admin password OTP to " . $email . " for admin ID " . $admin_id);
        // Clear session data if email sending failed
        unset($_SESSION['admin_password_change_otp']);
        unset($_SESSION['admin_password_change_otp_expiry']);
        unset($_SESSION['admin_password_change_id']);
        unset($_SESSION['admin_password_change_email_for_otp']);
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again later.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
