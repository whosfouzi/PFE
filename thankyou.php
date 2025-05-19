<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "giftstore");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$order_id = $_GET['order_id'] ?? null; // Get order ID from URL

$order_details = null;
$order_items = [];
$unique_id = time() . mt_rand(); // Default unique ID if order_id is not available or valid

if ($order_id) {
    // Fetch main order details
    $stmt_order = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    if ($stmt_order) {
        $stmt_order->bind_param("ii", $order_id, $_SESSION['id']); // Ensure user owns the order
        $stmt_order->execute();
        $result_order = $stmt_order->get_result();
        if ($result_order->num_rows > 0) {
            $order_details = $result_order->fetch_assoc();
            $unique_id = $order_details['id']; // Use actual order ID as reference
        }
        $stmt_order->close();
    }

    // Fetch order items if order details were found
    if ($order_details) {
        $stmt_items = $conn->prepare("SELECT product_name, quantity, unit_price FROM order_items WHERE order_id = ?");
        if ($stmt_items) {
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $order_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_items->close();
        }
    }
}

// Calculate estimated delivery date (e.g., 3-5 days from now)
$delivery_date_min = date('M d, Y', strtotime('+3 days'));
$delivery_date_max = date('M d, Y', strtotime('+5 days'));

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Thank You - GiftStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
    }
    .pacifico-font {
      font-family: 'Pacifico', cursive;
    }
    /* Custom styles for the specific turquoise color */
    .text-turquoise-primary {
        color: #56c8d8;
    }
    .bg-turquoise-primary {
        background-color: #56c8d8;
    }
    .border-turquoise-primary {
        border-color: #56c8d8;
    }
  </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

<?php include("navbar.php"); ?>

<div class="flex-grow container mx-auto px-4 py-12 text-center flex items-center justify-center">
  <div class="bg-white p-10 md:p-12 rounded-lg shadow-2xl max-w-2xl mx-auto border-t-8 border-turquoise-primary">
    <div class="text-6xl mb-6 animate-bounce-once">
      🎉
    </div>
    <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4 tracking-tight">
      Thank You for Your Order!
    </h1>
    <p class="text-gray-700 text-lg md:text-xl mb-6">
      Your order has been placed successfully and is being processed.
    </p>

    <?php if ($order_details): ?>
        <div class="bg-cyan-50 p-6 rounded-lg mb-8 text-left border border-cyan-200">
            <h2 class="text-xl font-bold text-turquoise-primary mb-4">Order Summary</h2>
            <p class="text-gray-700 mb-2"><strong>Order ID:</strong> <span class="font-mono bg-cyan-100 px-2 py-1 rounded text-cyan-800"><?= htmlspecialchars($order_details['id']) ?></span></p>
            <p class="text-gray-700 mb-2"><strong>Order Date:</strong> <?= date('M d, Y h:i A', strtotime($order_details['created_at'])) ?></p>
            <p class="text-gray-700 mb-2"><strong>Total Amount:</strong> <span class="text-turquoise-primary font-bold">DA <?= number_format($order_details['total_price'], 2) ?></span></p>
            <h3 class="text-lg font-semibold text-turquoise-primary mb-2">Items Purchased:</h3>
            <ul class="list-disc list-inside text-gray-700 space-y-1">
                <?php foreach ($order_items as $item): ?>
                    <li><?= htmlspecialchars($item['product_name']) ?> (x<?= $item['quantity'] ?>) - DA <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="bg-green-50 p-6 rounded-lg mb-8 text-left border border-green-200">
            <h2 class="text-xl font-bold text-green-700 mb-3">What's Next?</h2>
            <p class="text-gray-700 mb-2">
                Your order is now being prepared for shipment. You will receive an email confirmation shortly.
            </p>
            <p class="text-gray-700 font-semibold">
                Estimated Delivery: <span class="text-turquoise-primary"><?= $delivery_date_min ?> - <?= $delivery_date_max ?></span>
            </p>
            <p class="text-gray-700 mt-3">
                You can track your order status in your <a href="my_account.php#orders" class="text-blue-600 hover:underline font-medium">My Orders</a> section.
            </p>
        </div>

    <?php else: ?>
        <p class="text-sm text-gray-500 mb-6">
            Reference ID: <span class="font-mono bg-gray-200 px-2 py-1 rounded"><?= $unique_id ?></span>
        </p>
        <p class="text-red-500 mb-4">
            Could not retrieve specific order details. Please check your "My Orders" page.
        </p>
    <?php endif; ?>

    <div class="flex justify-center gap-4 mt-6">
        <a href="index.php" class="inline-block bg-turquoise-primary text-white px-8 py-3 rounded-lg hover:bg-cyan-700 transition transform hover:scale-105 shadow-md text-lg font-semibold">
            Continue Shopping
        </a>
        <?php if ($order_details): ?>
            <a href="my_account.php#orders" class="inline-block bg-gray-200 text-gray-800 px-8 py-3 rounded-lg hover:bg-gray-300 transition transform hover:scale-105 shadow-md text-lg font-semibold">
                View My Orders
            </a>
        <?php endif; ?>
    </div>
  </div>
</div>

<?php include("footer.php"); ?>
</body>
</html>