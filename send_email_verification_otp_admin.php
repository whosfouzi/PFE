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
    $new_email = $_POST['new_email'] ?? ''; // This is the email the admin WANTS to change TO
    $admin_id = $_SESSION['id'];

    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid new email address.']);
        exit();
    }

    $db = new mysqli("localhost", "root", "", "giftstore");
    if ($db->connect_error) {
        error_log("Database Connection failed in send_email_verification_otp_admin.php: " . $db->connect_error);
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
        exit();
    }

    // FIRST: Check if the NEW email is already taken by another user (excluding current admin)
    $stmt_check_new_email = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    if (!$stmt_check_new_email) {
        error_log("Prepare failed for new email check in send_email_verification_otp_admin.php: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Database error during new email check.']);
        $db->close();
        exit();
    }
    $stmt_check_new_email->bind_param("si", $new_email, $admin_id);
    $stmt_check_new_email->execute();
    $stmt_check_new_email->store_result();
    if ($stmt_check_new_email->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This new email address is already taken by another account.']);
        $stmt_check_new_email->close();
        $db->close();
        exit();
    }
    $stmt_check_new_email->close();

    // SECOND: Fetch the admin's CURRENT email to send the OTP to
    $stmt_get_current_email = $db->prepare("SELECT email FROM users WHERE id = ? AND role = 'admin'");
    if (!$stmt_get_current_email) {
        error_log("Prepare failed for current email fetch in send_email_verification_otp_admin.php: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Database error during current email fetch.']);
        $db->close();
        exit();
    }
    $stmt_get_current_email->bind_param("i", $admin_id);
    $stmt_get_current_email->execute();
    $result_current_email = $stmt_get_current_email->get_result();
    $current_admin_data = $result_current_email->fetch_assoc();
    $stmt_get_current_email->close();
    $db->close();

    if (!$current_admin_data || empty($current_admin_data['email'])) {
        echo json_encode(['success' => false, 'message' => 'Could not retrieve your current email address.']);
        exit();
    }

    $original_email = $current_admin_data['email']; // This is the email to send the OTP to

    // Generate a 6-digit OTP
    $otp = rand(100000, 999999);
    // Set OTP expiry time (e.g., 10 minutes from now)
    $otp_expiry = time() + (10 * 60); // Current time + 10 minutes

    // Store OTP, its expiry, the NEW email, and the admin ID in the session
    $_SESSION['admin_email_change_otp'] = $otp;
    $_SESSION['admin_email_change_otp_expiry'] = $otp_expiry;
    $_SESSION['admin_email_change_new_email_pending'] = $new_email; // Store the new email the admin wants
    $_SESSION['admin_email_change_id'] = $admin_id; // Store admin ID to prevent cross-session attacks

    // Send the OTP via email to the ORIGINAL email address
    $subject = "Your Admin Email Change Verification Code for SefarGifts";
    $body = "Dear Admin,<br><br>You have requested to change your email address on SefarGifts Admin Panel. Your One-Time Password (OTP) for verification is: <b>" . $otp . "</b>. This code is valid for 10 minutes.<br><br>If you did not request this email change, please ignore this email or contact support immediately.<br><br>Best regards,<br>The SefarGifts Admin Team";

    $mailSent = sendEmail($original_email, $subject, $body);

    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'OTP sent to your current email! Check your inbox (and spam).']);
    } else {
        error_log("Failed to send admin email OTP to " . $original_email . " for admin ID " . $admin_id);
        // Clear session data if email sending failed to avoid invalid attempts
        unset($_SESSION['admin_email_change_otp']);
        unset($_SESSION['admin_email_change_otp_expiry']);
        unset($_SESSION['admin_email_change_new_email_pending']);
        unset($_SESSION['admin_email_change_id']);
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again later.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
