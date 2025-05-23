<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'] ?? '';
    // We expect the new_email to be in session, but client also sends it (for consistency).
    // The authoritative source for the new email should be the session.
    $new_email_from_client = $_POST['new_email_submitted'] ?? '';
    $user_id = $_SESSION['id'];

    // Retrieve OTP and new email from session
    $stored_otp = $_SESSION['email_change_otp'] ?? null;
    $stored_otp_expiry = $_SESSION['email_change_otp_expiry'] ?? null;
    $stored_new_email_pending = $_SESSION['email_change_new_email_pending'] ?? null;
    $stored_user_id_for_email_change = $_SESSION['email_change_user_id'] ?? null;

    // Validate OTP and expiry
    if ($stored_otp === null || $stored_otp_expiry === null || $stored_new_email_pending === null || $stored_user_id_for_email_change === null) {
        echo json_encode(['success' => false, 'message' => 'No pending email change request or session expired. Please resend OTP.']);
        exit();
    }

    // Basic security check: ensure the session's user ID matches the current user
    if ($stored_user_id_for_email_change != $user_id) {
        // This indicates a potential session mix-up or tampering
        error_log("Security Alert: User ID mismatch during email change verification. Session User ID: " . $stored_user_id_for_email_change . ", Current User ID: " . $user_id);
        // Clear session data to prevent further attempts with invalid context
        unset($_SESSION['email_change_otp']);
        unset($_SESSION['email_change_otp_expiry']);
        unset($_SESSION['email_change_new_email_pending']);
        unset($_SESSION['email_change_user_id']);
        echo json_encode(['success' => false, 'message' => 'Security check failed. Please re-initiate email change.']);
        exit();
    }

    if (time() > $stored_otp_expiry) {
        // Clear session data as OTP expired
        unset($_SESSION['email_change_otp']);
        unset($_SESSION['email_change_otp_expiry']);
        unset($_SESSION['email_change_new_email_pending']);
        unset($_SESSION['email_change_user_id']);
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please resend OTP.']);
        exit();
    }

    if ($entered_otp != $stored_otp) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
        exit();
    }

    // OTP is valid and not expired, proceed to update email
    $db = new mysqli("localhost", "root", "", "giftstore");
    if ($db->connect_error) {
        error_log("Database Connection failed in update_email_with_otp.php: " . $db->connect_error);
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit();
    }

    // It's crucial to use $stored_new_email_pending from the session, not the one from client submission,
    // as the session value is the one we verified with the OTP.
    $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed in update_email_with_otp.php: " . $db->error);
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Database error during update.']);
        exit();
    }

    $stmt->bind_param("si", $stored_new_email_pending, $user_id);

    if ($stmt->execute()) {
        // Email updated successfully. Clear OTP-related session data.
        unset($_SESSION['email_change_otp']);
        unset($_SESSION['email_change_otp_expiry']);
        unset($_SESSION['email_change_new_email_pending']);
        unset($_SESSION['email_change_user_id']); // Clear this too

        // Optionally, update the user's email in the current session
        $_SESSION['email'] = $stored_new_email_pending;

        echo json_encode(['success' => true, 'message' => 'Email updated successfully.']);
    } else {
        error_log("Execute failed in update_email_with_otp.php: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update email. Please try again.']);
    }

    $stmt->close();
    $db->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>