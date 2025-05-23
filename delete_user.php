<?php
session_start();
header('Content-Type: application/json');

// Check if the user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Database connection (assuming the same as admin.php)
$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    error_log("Database Connection failed in delete_user.php: " . $db->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}
$db->set_charset("utf8mb4");

// Check if user ID is provided via POST
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit();
}

$user_id = (int)$_POST['id'];

// Prepare and execute the DELETE statement
$stmt = $db->prepare("DELETE FROM users WHERE id = ?");
if ($stmt === false) {
    error_log("Prepare failed in delete_user.php: " . $db->error);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    exit();
}

$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found or already deleted.']);
    }
} else {
    error_log("Execute failed in delete_user.php: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to delete user.']);
}

$stmt->close();
$db->close();
?>