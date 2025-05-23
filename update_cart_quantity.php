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
    error_log("Database Connection failed in update_cart_quantity.php: " . $db->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}
$db->set_charset("utf8mb4");

// Check if required data is present
if (!isset($_POST['item_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing item ID or quantity.']);
    $db->close();
    exit();
}

$itemId = intval($_POST['item_id']);
$newQuantity = intval($_POST['quantity']);

// Fetch cart ID for the user
$cartId = null;
$stmt = $db->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($cartId);
$stmt->fetch();
$stmt->close();

if (!$cartId) {
    echo json_encode(['success' => false, 'message' => 'Cart not found for user.']);
    $db->close();
    exit();
}

// Check product stock if quantity is increasing
if ($newQuantity > 0) {
    $stock_check = $db->prepare("SELECT stock FROM products WHERE id = ?");
    $stock_check->bind_param("i", $itemId);
    $stock_check->execute();
    $stock_check->bind_result($availableStock);
    $stock_check->fetch();
    $stock_check->close();

    if ($newQuantity > $availableStock) {
        echo json_encode(['success' => false, 'message' => "Not enough stock. Available: {$availableStock}."]);
        $db->close();
        exit();
    }
}

// Update or delete item in cart_items
if ($newQuantity > 0) {
    // Check if item already exists in cart_items
    $stmt_check_item = $db->prepare("SELECT id FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt_check_item->bind_param("ii", $cartId, $itemId);
    $stmt_check_item->execute();
    $stmt_check_item->store_result();

    if ($stmt_check_item->num_rows > 0) {
        // Item exists, update its quantity
        $stmt_update = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
        $stmt_update->bind_param("iii", $newQuantity, $cartId, $itemId);
        if ($stmt_update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Quantity updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update quantity.']);
        }
        $stmt_update->close();
    } else {
        // Item does not exist, insert it
        $stmt_insert = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iii", $cartId, $itemId, $newQuantity);
        if ($stmt_insert->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add item to cart.']);
        }
        $stmt_insert->close();
    }
    $stmt_check_item->close();
} else {
    // New quantity is 0, remove item from cart
    $stmt_delete = $db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt_delete->bind_param("ii", $cartId, $itemId);
    if ($stmt_delete->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item.']);
    }
    $stmt_delete->close();
}

$db->close();
?>