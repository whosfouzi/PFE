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
    error_log("Database Connection failed in clear_cart.php: " . $db->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}
$db->set_charset("utf8mb4");

// Start transaction for atomicity
$db->autocommit(false);

try {
    // Find the user's cart ID
    $cartId = null;
    $stmt = $db->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($cartId);
    $stmt->fetch();
    $stmt->close();

    if ($cartId) {
        // Delete all items associated with this cart
        $stmt_items = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt_items->bind_param("i", $cartId);
        $stmt_items->execute();
        $stmt_items->close();

        // Optionally, delete the cart itself if it should be removed when empty
        // $stmt_cart = $db->prepare("DELETE FROM cart WHERE id = ?");
        // $stmt_cart->bind_param("i", $cartId);
        // $stmt_cart->execute();
        // $stmt_cart->close();
    }

    $db->commit();
    unset($_SESSION["shopping_cart"]); // Clear session cart as well
    echo json_encode(['success' => true, 'message' => 'Your cart has been cleared.']);

} catch (Exception $e) {
    $db->rollback();
    error_log("Error clearing cart for user {$userId}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to clear cart.']);
} finally {
    $db->close();
}
?>