<?php
session_start();
header('Content-Type: application/json');

// Ensure only admin can access this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? ''; // Email submitted from the form (should match session)
    $new_password = $_POST['new_password'] ?? '';
    $entered_otp = $_POST['otp'] ?? '';
    $admin_id = $_SESSION['id'];

    // Retrieve OTP and related data from session
    $stored_otp = $_SESSION['admin_password_change_otp'] ?? null;
    $stored_otp_expiry = $_SESSION['admin_password_change_otp_expiry'] ?? null;
    $stored_admin_id_for_password_change = $_SESSION['admin_password_change_id'] ?? null;
    $stored_email_for_otp = $_SESSION['admin_password_change_email_for_otp'] ?? null;

    // Validate session data presence
    if ($stored_otp === null || $stored_otp_expiry === null || $stored_admin_id_for_password_change === null || $stored_email_for_otp === null) {
        echo json_encode(['success' => false, 'message' => 'No pending password change request or session expired. Please resend OTP.']);
        exit();
    }

    // Security checks: Match admin ID and email used for OTP
    if ($stored_admin_id_for_password_change != $admin_id || $stored_email_for_otp !== $email) {
        error_log("Security Alert: Admin ID or Email mismatch during password change verification. Session Admin ID: " . $stored_admin_id_for_password_change . ", Current Admin ID: " . $admin_id . ", Session Email: " . $stored_email_for_otp . ", Submitted Email: " . $email);
        unset($_SESSION['admin_password_change_otp']);
        unset($_SESSION['admin_password_change_otp_expiry']);
        unset($_SESSION['admin_password_change_id']);
        unset($_SESSION['admin_password_change_email_for_otp']);
        echo json_encode(['success' => false, 'message' => 'Security check failed. Please re-initiate password change.']);
        exit();
    }

    // Validate OTP expiry
    if (time() > $stored_otp_expiry) {
        unset($_SESSION['admin_password_change_otp']);
        unset($_SESSION['admin_password_change_otp_expiry']);
        unset($_SESSION['admin_password_change_id']);
        unset($_SESSION['admin_password_change_email_for_otp']);
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please resend OTP.']);
        exit();
    }

    // Validate entered OTP
    if ($entered_otp != $stored_otp) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
        exit();
    }

    // Validate new password strength (add more rules as needed)
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
        exit();
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in the database
    $db = new mysqli("localhost", "root", "", "giftstore");
    if ($db->connect_error) {
        error_log("Database Connection failed in update_admin_password_with_otp.php: " . $db->connect_error);
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit();
    }

    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
    if (!$stmt) {
        error_log("Prepare failed in update_admin_password_with_otp.php: " . $db->error);
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Database error during update.']);
        exit();
    }

    $stmt->bind_param("si", $hashed_password, $admin_id);

    if ($stmt->execute()) {
        // Password updated successfully. Clear OTP-related session data.
        unset($_SESSION['admin_password_change_otp']);
        unset($_SESSION['admin_password_change_otp_expiry']);
        unset($_SESSION['admin_password_change_id']);
        unset($_SESSION['admin_password_change_email_for_otp']);

        echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
    } else {
        error_log("Execute failed in update_admin_password_with_otp.php: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
    }

    $stmt->close();
    $db->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
