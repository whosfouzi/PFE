<?php
session_start();

// Ensure only admins can access this script
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo "<p class='text-red-500'>Unauthorized access.</p>";
    exit();
}

// Database connection
$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    echo "<p class='text-red-500'>Database connection failed: " . $db->connect_error . "</p>";
    exit();
}
$db->set_charset("utf8mb4");

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id === 0) {
    echo "<p class='text-red-500'>Invalid order ID.</p>";
    $db->close();
    exit();
}

// Fetch order details including client information from the orders table
$stmt_order = $db->prepare("SELECT user_name, email, phone, address, total_price, order_status, created_at FROM orders WHERE id = ?");
if ($stmt_order === false) {
    echo "<p class='text-red-500'>Failed to prepare order details statement: " . $db->error . "</p>";
    $db->close();
    exit();
}
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();
$order_details = $result_order->fetch_assoc();
$stmt_order->close();

if (!$order_details) {
    echo "<p class='text-red-500'>Order not found.</p>";
    $db->close();
    exit();
}

// Fetch order items
$stmt_items = $db->prepare("SELECT product_name, quantity, unit_price FROM order_items WHERE order_id = ?");
if ($stmt_items === false) {
    echo "<p class='text-red-500'>Failed to prepare order items statement: " . $db->error . "</p>";
    $db->close();
    exit();
}
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$order_items = $result_items->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();

$db->close();

// Generate HTML output
?>
<div class="space-y-4 mb-6 pb-4 border-b border-gray-200">
    <h4 class="text-xl font-semibold text-gray-800">Client Information</h4>
    <p><strong class="text-gray-700">Name:</strong> <?= htmlspecialchars($order_details['user_name']) ?></p>
    <p><strong class="text-gray-700">Email:</strong> <?= htmlspecialchars($order_details['email']) ?></p>
    <p><strong class="text-gray-700">Phone:</strong> <?= htmlspecialchars($order_details['phone']) ?></p>
    <p><strong class="text-gray-700">Address:</strong> <?= htmlspecialchars($order_details['address']) ?></p>
    <p><strong class="text-gray-700">Order Date:</strong> <?= date('Y-m-d H:i', strtotime($order_details['created_at'])) ?></p>
    <p><strong class="text-gray-700">Status:</strong> <span class="font-bold text-admin-primary"><?= ucfirst(htmlspecialchars($order_details['order_status'])) ?></span></p>
    <p><strong class="text-gray-700">Total Price:</strong> <span class="font-bold text-admin-primary">DA <?= number_format($order_details['total_price'], 2) ?></span></p>
</div>

<h4 class="text-xl font-semibold text-gray-800 mb-4">Order Items</h4>
<?php if (!empty($order_items)): ?>
    <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 admin-table">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['quantity']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">DA <?= number_format($item['unit_price'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">DA <?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-gray-600">No items found for this order.</p>
<?php endif; ?>
