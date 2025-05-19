<?php
session_start();
header('Content-Type: application/json'); // Ensure JSON response

// Database connection (same as your products.php)
$conn = new mysqli("localhost", "root", "", "giftstore");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error, 'loggedIn' => isset($_SESSION['id'])]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.', 'loggedIn' => false]);
    exit();
}

$user_id = $_SESSION['id'];

// Check if product_id is received
if (!isset($_POST['product_id']) || !filter_var($_POST['product_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.', 'loggedIn' => true]);
    exit();
}

$product_id = (int)$_POST['product_id'];

// Check if the product is already liked by the user
$stmt_check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
if (!$stmt_check) {
    echo json_encode(['success' => false, 'message' => 'Prepare statement failed (check): ' . $conn->error, 'loggedIn' => true]);
    exit();
}
$stmt_check->bind_param("ii", $user_id, $product_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$is_liked = $result_check->num_rows > 0;
$stmt_check->close();

$new_liked_status = false;

if ($is_liked) {
    // Product is liked, so unlike it (delete from wishlist)
    $stmt_delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    if (!$stmt_delete) {
        echo json_encode(['success' => false, 'message' => 'Prepare statement failed (delete): ' . $conn->error, 'loggedIn' => true]);
        exit();
    }
    $stmt_delete->bind_param("ii", $user_id, $product_id);
    if ($stmt_delete->execute()) {
        $new_liked_status = false; // Successfully unliked
        echo json_encode(['success' => true, 'liked' => $new_liked_status, 'action' => 'unliked', 'loggedIn' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unlike product: ' . $stmt_delete->error, 'loggedIn' => true]);
    }
    $stmt_delete->close();
} else {
    // Product is not liked, so like it (insert into wishlist)
    $stmt_insert = $conn->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
     if (!$stmt_insert) {
        echo json_encode(['success' => false, 'message' => 'Prepare statement failed (insert): ' . $conn->error, 'loggedIn' => true]);
        exit();
    }
    $stmt_insert->bind_param("ii", $user_id, $product_id);
    if ($stmt_insert->execute()) {
        $new_liked_status = true; // Successfully liked
        echo json_encode(['success' => true, 'liked' => $new_liked_status, 'action' => 'liked', 'loggedIn' => true]);
    } else {
        // Check for duplicate entry error (though the previous check should prevent this)
        if ($conn->errno == 1062) { // 1062 is MySQL error code for duplicate entry
             echo json_encode(['success' => false, 'message' => 'Product already liked (duplicate entry).', 'liked' => true, 'loggedIn' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to like product: ' . $stmt_insert->error, 'loggedIn' => true]);
        }
    }
    $stmt_insert->close();
}

$conn->close();
?>