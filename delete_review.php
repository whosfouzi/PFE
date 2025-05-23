<?php
session_start();
header('Content-Type: application/json');

// --- DEBUG START ---
// Log all POST data received
error_log("delete_review.php: POST data received: " . print_r($_POST, true));
// --- DEBUG END ---

// Check if the user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    error_log("delete_review.php: Unauthorized access attempt.");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Database connection (assuming the same as admin.php)
$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    error_log("Database Connection failed in delete_review.php: " . $db->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}
$db->set_charset("utf8mb4");

// Check if review ID is provided via POST and is numeric
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    // --- DEBUG START ---
    error_log("delete_review.php: Invalid review ID. POST['id'] is " . (isset($_POST['id']) ? $_POST['id'] : 'not set') . " (type: " . (isset($_POST['id']) ? gettype($_POST['id']) : 'N/A') . ")");
    // --- DEBUG END ---
    echo json_encode(['success' => false, 'message' => 'Invalid review ID.']);
    exit();
}

$review_id = (int)$_POST['id'];

// Prepare and execute the DELETE statement
$stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
if ($stmt === false) {
    error_log("Prepare failed in delete_review.php: " . $db->error);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    exit();
}

$stmt->bind_param("i", $review_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        error_log("delete_review.php: Review ID {$review_id} deleted successfully.");
        echo json_encode(['success' => true, 'message' => 'Review deleted successfully.']);
    } else {
        error_log("delete_review.php: Review ID {$review_id} not found or already deleted.");
        echo json_encode(['success' => false, 'message' => 'Review not found or already deleted.']);
    }
} else {
    error_log("Execute failed in delete_review.php for ID {$review_id}: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to delete review.']);
}

$stmt->close();
$db->close();
?>