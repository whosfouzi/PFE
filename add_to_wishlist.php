<?php
session_start();
header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$userId = $_SESSION['id'];

// Database connection
$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    error_log("Database Connection failed in add_to_wishlist.php: " . $db->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}
$db->set_charset("utf8mb4");

// Check if product ID is provided
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid product ID.']);
    $db->close();
    exit();
}

$productId = intval($_POST['product_id']);

// Check if the item is already in the wishlist to prevent duplicates
// Assuming you have a 'wishlist' table with 'user_id' and 'product_id'
$stmt_check = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
$stmt_check->bind_param("ii", $userId, $productId);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Item is already in your wishlist.']);
} else {
    // Insert into wishlist
    $stmt_insert = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt_insert->bind_param("ii", $userId, $productId);
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item added to wishlist.']);
    } else {
        error_log("Failed to add to wishlist for user {$userId}, product {$productId}: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Failed to add item to wishlist.']);
    }
    $stmt_insert->close();
}
$stmt_check->close();
$db->close();
?>