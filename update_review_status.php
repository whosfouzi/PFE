<?php
session_start();
header('Content-Type: application/json'); // Crucial: Set content type to JSON

// Check if the user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    error_log("update_review_status.php: Unauthorized access attempt.");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if required data is present
    if (!isset($_POST['review_id']) || !isset($_POST['new_status'])) {
        error_log("update_review_status.php: Missing data (review_id or new_status).");
        echo json_encode(['success' => false, 'message' => 'Missing data.']);
        exit();
    }

    $review_id = intval($_POST['review_id']);
    $new_status = intval($_POST['new_status']); // Should be 0 or 1

    // Validate new_status to ensure it's either 0 or 1
    if ($new_status !== 0 && $new_status !== 1) {
        error_log("update_review_status.php: Invalid status value: {$new_status}. Must be 0 or 1.");
        echo json_encode(['success' => false, 'message' => 'Invalid status value. Status must be 0 or 1.']);
        exit();
    }

    $db = new mysqli("localhost", "root", "", "giftstore");

    if ($db->connect_error) {
        error_log("Database Connection failed in update_review_status.php: " . $db->connect_error);
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit();
    }
    $db->set_charset("utf8mb4");

    // Prepare and execute the update statement
    $stmt = $db->prepare("UPDATE reviews SET is_featured = ? WHERE id = ?");
    if ($stmt === false) {
        error_log("Prepare failed in update_review_status.php: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
        exit();
    }

    $stmt->bind_param("ii", $new_status, $review_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            error_log("update_review_status.php: Review ID {$review_id} status updated to {$new_status} successfully.");
            echo json_encode(['success' => true, 'message' => 'Review status updated successfully.']);
        } else {
            error_log("update_review_status.php: Review ID {$review_id} not found or status already {$new_status}.");
            echo json_encode(['success' => false, 'message' => 'Review not found or status already set.']);
        }
    } else {
        error_log("Execute failed in update_review_status.php for ID {$review_id}: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update review status: ' . $stmt->error]);
    }

    $stmt->close();
    $db->close();
} else {
    error_log("update_review_status.php: Invalid request method. Only POST is allowed.");
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
