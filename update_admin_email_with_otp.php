<?php
session_start();
header('Content-Type: application/json');

// Ensure only admin can access this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'] ?? '';
    $new_email_from_client = $_POST['new_email_submitted'] ?? ''; // For consistency, but session is authoritative
    $admin_id = $_SESSION['id'];

    // Retrieve OTP and new email from session
    $stored_otp = $_SESSION['admin_email_change_otp'] ?? null;
    $stored_otp_expiry = $_SESSION['admin_email_change_otp_expiry'] ?? null;
    $stored_new_email_pending = $_SESSION['admin_email_change_new_email_pending'] ?? null;
    $stored_admin_id_for_email_change = $_SESSION['admin_email_change_id'] ?? null;

    // Validate OTP and expiry
    if ($stored_otp === null || $stored_otp_expiry === null || $stored_new_email_pending === null || $stored_admin_id_for_email_change === null) {
        echo json_encode(['success' => false, 'message' => 'No pending email change request or session expired. Please resend OTP.']);
        exit();
    }

    // Security check: ensure the session's admin ID matches the current admin
    if ($stored_admin_id_for_email_change != $admin_id) {
        error_log("Security Alert: Admin ID mismatch during email change verification. Session Admin ID: " . $stored_admin_id_for_email_change . ", Current Admin ID: " . $admin_id);
        unset($_SESSION['admin_email_change_otp']);
        unset($_SESSION['admin_email_change_otp_expiry']);
        unset($_SESSION['admin_email_change_new_email_pending']);
        unset($_SESSION['admin_email_change_id']);
        echo json_encode(['success' => false, 'message' => 'Security check failed. Please re-initiate email change.']);
        exit();
    }

    if (time() > $stored_otp_expiry) {
        unset($_SESSION['admin_email_change_otp']);
        unset($_SESSION['admin_email_change_otp_expiry']);
        unset($_SESSION['admin_email_change_new_email_pending']);
        unset($_SESSION['admin_email_change_id']);
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
        error_log("Database Connection failed in update_admin_email_with_otp.php: " . $db->connect_error);
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit();
    }

    // Use $stored_new_email_pending from the session
    $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ? AND role = 'admin'");
    if (!$stmt) {
        error_log("Prepare failed in update_admin_email_with_otp.php: " . $db->error);
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Database error during update.']);
        exit();
    }

    $stmt->bind_param("si", $stored_new_email_pending, $admin_id);

    if ($stmt->execute()) {
        // Email updated successfully. Clear OTP-related session data.
        unset($_SESSION['admin_email_change_otp']);
        unset($_SESSION['admin_email_change_otp_expiry']);
        unset($_SESSION['admin_email_change_new_email_pending']);
        unset($_SESSION['admin_email_change_id']);

        // Update the admin's email in the current session
        $_SESSION['email'] = $stored_new_email_pending;

        echo json_encode(['success' => true, 'message' => 'Email updated successfully.']);
    } else {
        error_log("Execute failed in update_admin_email_with_otp.php: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update email. Please try again.']);
    }

    $stmt->close();
    $db->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
