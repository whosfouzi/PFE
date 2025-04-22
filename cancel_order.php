<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'client') {
    http_response_code(403);
    echo "Unauthorized access.";
    exit();
}

if (!isset($_POST['order_id'])) {
    http_response_code(400);
    echo "Missing order ID.";
    exit();
}

$order_id = intval($_POST['order_id']);
$user_email = $_SESSION['email'];

// Connect to DB
$conn = new mysqli("localhost", "root", "", "giftstore");
if ($conn->connect_error) {
    http_response_code(500);
    echo "Database connection failed.";
    exit();
}

// Ensure the order belongs to the logged-in user and isn't already cancelled
$check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND email = ? AND order_status != 'cancelled'");
$check->bind_param("is", $order_id, $user_email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo "Order not found or already cancelled.";
    exit();
}

// Cancel the order
$update = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?");
$update->bind_param("i", $order_id);
if ($update->execute()) {
    echo "Order cancelled successfully.";
} else {
    http_response_code(500);
    echo "Failed to cancel order.";
}
?>
