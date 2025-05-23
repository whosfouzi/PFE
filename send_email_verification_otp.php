<?php
session_start();
header('Content-Type: application/json');

// Include your database connection and email sending functions
// Assuming you have a file that handles sending emails, e.g., 'send_email.php'
include('send_email.php'); // Make sure this file exists and contains your email sending logic

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = $_POST['new_email'] ?? ''; // This is the email the user WANTS to change TO
    $user_id = $_SESSION['id'];

    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit();
    }

    // Connect to database
    $db = new mysqli("localhost", "root", "", "giftstore");
    if ($db->connect_error) {
        error_log("Database Connection failed in send_email_verification_otp.php: " . $db->connect_error);
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
        exit();
    }

    // FIRST: Check if the NEW email is already taken by another user
    $stmt_check_new_email = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    if (!$stmt_check_new_email) {
        error_log("Prepare failed for new email check in send_email_verification_otp.php: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Database error during new email check.']);
        $db->close();
        exit();
    }
    $stmt_check_new_email->bind_param("si", $new_email, $user_id);
    $stmt_check_new_email->execute();
    $stmt_check_new_email->store_result();
    if ($stmt_check_new_email->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This new email address is already taken by another account.']);
        $stmt_check_new_email->close();
        $db->close();
        exit();
    }
    $stmt_check_new_email->close();

    // SECOND: Fetch the user's CURRENT email to send the OTP to
    $stmt_get_current_email = $db->prepare("SELECT email FROM users WHERE id = ?");
    if (!$stmt_get_current_email) {
        error_log("Prepare failed for current email fetch in send_email_verification_otp.php: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Database error during current email fetch.']);
        $db->close();
        exit();
    }
    $stmt_get_current_email->bind_param("i", $user_id);
    $stmt_get_current_email->execute();
    $result_current_email = $stmt_get_current_email->get_result();
    $current_user_data = $result_current_email->fetch_assoc();
    $stmt_get_current_email->close();
    $db->close(); // Close DB connection after fetching necessary data

    if (!$current_user_data || empty($current_user_data['email'])) {
        echo json_encode(['success' => false, 'message' => 'Could not retrieve your current email address.']);
        exit();
    }

    $original_email = $current_user_data['email']; // This is the email to send the OTP to

    // Generate a 6-digit OTP
    $otp = rand(100000, 999999);
    // Set OTP expiry time (e.g., 10 minutes from now)
    $otp_expiry = time() + (10 * 60); // Current time + 10 minutes

    // Store OTP, its expiry, the NEW email, and the user ID in the session
    $_SESSION['email_change_otp'] = $otp;
    $_SESSION['email_change_otp_expiry'] = $otp_expiry;
    $_SESSION['email_change_new_email_pending'] = $new_email; // Store the new email the user wants
    $_SESSION['email_change_user_id'] = $user_id; // Store user ID to prevent cross-session attacks

    // Send the OTP via email to the ORIGINAL email address
    $subject = "Your Email Change Verification Code for SefarGifts";
    $body = "Dear User,<br><br>You have requested to change your email address on SefarGifts. Your One-Time Password (OTP) for verification is: <b>" . $otp . "</b>. This code is valid for 10 minutes.<br><br>If you did not request this email change, please ignore this email or contact support immediately.<br><br>Best regards,<br>The SefarGifts Team";

    // Assuming sendEmail function exists in send_email.php and works like this:
    $mailSent = sendEmail($original_email, $subject, $body); // Send to ORIGINAL email

    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'OTP sent to your current email! Check your inbox (and spam).']);
    } else {
        error_log("Failed to send OTP email to " . $original_email . " for user ID " . $user_id);
        // Clear session data if email sending failed to avoid invalid attempts
        unset($_SESSION['email_change_otp']);
        unset($_SESSION['email_change_otp_expiry']);
        unset($_SESSION['email_change_new_email_pending']);
        unset($_SESSION['email_change_user_id']);
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again later.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
