<?php
session_start();
header('Content-Type: application/json');

// Ensure only admins can access this script
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $db->connect_error]);
    exit();
}
$db->set_charset("utf8mb4");

$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$new_status = isset($_POST['order_status']) ? $_POST['order_status'] : '';

if ($order_id === 0 || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or status provided.']);
    $db->close();
    exit();
}

// Start transaction
$db->autocommit(false);

try {
    // Get current order status to prevent double stock updates
    $stmt_current_status = $db->prepare("SELECT order_status FROM orders WHERE id = ?");
    $stmt_current_status->bind_param("i", $order_id);
    $stmt_current_status->execute();
    $result_current_status = $stmt_current_status->get_result();
    $current_order_status = $result_current_status->fetch_assoc()['order_status'] ?? null;
    $stmt_current_status->close();

    if ($current_order_status === null) {
        throw new Exception("Order not found.");
    }

    // Update order status
    $stmt_update_order = $db->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    if ($stmt_update_order === false) {
        throw new Exception("Failed to prepare order status update: " . $db->error);
    }
    $stmt_update_order->bind_param("si", $new_status, $order_id);
    if (!$stmt_update_order->execute()) {
        throw new Exception("Failed to update order status: " . $stmt_update_order->error);
    }
    $stmt_update_order->close();

    // If new status is 'returned' and old status was NOT 'returned', add stock back
    if ($new_status === 'returned' && $current_order_status !== 'returned') {
        // Fetch order items for this order
        $stmt_items = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        if ($stmt_items === false) {
            throw new Exception("Failed to prepare order items fetch: " . $db->error);
        }
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        $order_items = $result_items->fetch_all(MYSQLI_ASSOC);
        $stmt_items->close();

        if (!empty($order_items)) {
            foreach ($order_items as $item) {
                $stmt_update_stock = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                if ($stmt_update_stock === false) {
                    throw new Exception("Failed to prepare stock update: " . $db->error);
                }
                $stmt_update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                if (!$stmt_update_stock->execute()) {
                    throw new Exception("Failed to update stock for product ID " . $item['product_id'] . ": " . $stmt_update_stock->error);
                }
                $stmt_update_stock->close();
            }
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully.']);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $db->close();
}
?>
