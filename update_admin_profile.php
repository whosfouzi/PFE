<?php
session_start();
header('Content-Type: application/json');

// Ensure only admin can access this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    error_log("Database Connection failed in update_admin_profile.php: " . $db->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_SESSION['id']; // Get admin ID from session
    $username = $_POST['username'] ?? '';
    $fname = $_POST['fname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Basic validation
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username cannot be empty.']);
        $db->close();
        exit();
    }

    // Check if username is already taken by another user (excluding current admin)
    $stmt_check_username = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    if (!$stmt_check_username) {
        error_log("Prepare failed for username check: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Database error during username check.']);
        $db->close();
        exit();
    }
    $stmt_check_username->bind_param("si", $username, $admin_id);
    $stmt_check_username->execute();
    $stmt_check_username->store_result();
    if ($stmt_check_username->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username is already taken. Please choose another.']);
        $stmt_check_username->close();
        $db->close();
        exit();
    }
    $stmt_check_username->close();


    // Update admin profile in the database
    $stmt_update = $db->prepare("UPDATE users SET username = ?, fname = ?, lname = ?, phone = ? WHERE id = ? AND role = 'admin'");
    if (!$stmt_update) {
        error_log("Prepare failed for admin profile update: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Database error during profile update.']);
        $db->close();
        exit();
    }

    $stmt_update->bind_param("ssssi", $username, $fname, $lname, $phone, $admin_id);

    if ($stmt_update->execute()) {
        // Update session variables if necessary (e.g., if username is displayed in navbar)
        $_SESSION['username'] = $username;
        $_SESSION['fname'] = $fname;
        $_SESSION['lname'] = $lname;
        $_SESSION['phone'] = $phone;

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    } else {
        error_log("Execute failed for admin profile update: " . $stmt_update->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
    }

    $stmt_update->close();
    $db->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    $db->close();
}
?>
