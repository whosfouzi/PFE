<?php
session_start();
if (!isset($_SESSION["email"])) {
  header("Location: login.php");
  exit();
}
$conn = new mysqli("localhost", "root", "", "giftstore");

$user_email = $_SESSION["email"];

// Handle AJAX request to fetch order details
if (isset($_GET['fetch_order']) && isset($_GET['order_id'])) {
  $order_id = intval($_GET['order_id']);
  $stmt = $conn->prepare("SELECT product_name, quantity, unit_price FROM order_items WHERE order_id = ?");
  $stmt->bind_param("i", $order_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    echo "<table class='min-w-full text-sm text-left border'>
      <thead class='bg-pink-100 text-pink-700'>
        <tr>
          <th class='px-4 py-2'>Product</th>
          <th class='px-4 py-2'>Quantity</th>
          <th class='px-4 py-2'>Unit Price</th>
          <th class='px-4 py-2'>Subtotal</th>
        </tr>
      </thead>
      <tbody class='divide-y divide-gray-200'>";
    while ($item = $result->fetch_assoc()) {
      $subtotal = $item['unit_price'] * $item['quantity'];
      echo "<tr>
              <td class='px-4 py-2'>" . htmlspecialchars($item['product_name']) . "</td>
              <td class='px-4 py-2'>{$item['quantity']}</td>
              <td class='px-4 py-2'>€" . number_format($item['unit_price'], 2) . "</td>
              <td class='px-4 py-2'>€" . number_format($subtotal, 2) . "</td>
            </tr>";
    }
    echo "</tbody></table>";
  } else {
    echo "<p>No items found for this order.</p>";
  }
  exit();
}

// Handle AJAX request to cancel order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
  $order_id = intval($_POST['cancel_order']);
  $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ? AND email = ?");
  $stmt->bind_param("is", $order_id, $user_email);
  if ($stmt->execute()) {
    echo "success";
  } else {
    echo "fail";
  }
  exit();
}

$sql = "SELECT o.id, o.total_price, o.order_status, o.created_at,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o WHERE o.email = ? ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>My Orders - GiftStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
  <?php include("navbar.php"); ?>
  <div class="container mx-auto px-4 py-10">
    <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">📦 My Orders</h2>
    <?php if ($orders && $orders->num_rows > 0): ?>
      <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm text-left">
          <thead class="bg-pink-100 text-pink-700">
            <tr>
              <th class="px-6 py-3">Order ID</th>
              <th class="px-6 py-3">Items</th>
              <th class="px-6 py-3">Total</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3">Date</th>
              <th class="px-6 py-3">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php while ($order = $orders->fetch_assoc()): ?>
              <tr id="order-row-<?= $order["id"] ?>">
                <td class="px-6 py-4">#<?= $order["id"] ?></td>
                <td class="px-6 py-4"><?= $order["item_count"] ?></td>
                <td class="px-6 py-4">€<?= number_format($order["total_price"], 2) ?></td>
                <td class="px-6 py-4" id="status-<?= $order["id"] ?>">
                  <?php
                  $color = match ($order["order_status"]) {
                    "shipped" => "bg-green-500",
                    "cancelled" => "bg-red-500",
                    default => "bg-yellow-400"
                  };
                  ?>
                  <span class="text-white text-xs px-2 py-1 rounded-full <?= $color ?>">
                    <?= ucfirst($order["order_status"]) ?>
                  </span>
                </td>
                <td class="px-6 py-4"><?= date("Y-m-d", strtotime($order["created_at"])) ?></td>
                <td class="px-6 py-4">
                  <button onclick="viewOrderDetails(<?= $order['id'] ?>)"
                    class="text-blue-500 hover:underline text-sm">View</button>
                  <?php if ($order["order_status"] === "processing"): ?>
                    <div id="cancel-section-<?= $order['id'] ?>" class="inline">
                      <button onclick="cancelOrder(<?= $order['id'] ?>)"
                        class="text-red-500 hover:underline text-sm ml-2">Cancel</button>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="bg-white p-6 rounded-lg shadow text-center">
        <p class="text-gray-600 text-lg">You haven’t placed any orders yet.</p>
        <a href="index.php" class="mt-4 inline-block bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700">Shop
          Now</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- View Order Details Modal -->
  <div id="orderDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-2xl">
      <h2 class="text-xl font-bold mb-4">Order Details</h2>
      <div id="orderDetailsContent" class="text-sm text-gray-800">
        <!-- AJAX-loaded content -->
      </div>
      <div class="mt-4 text-right">
        <button onclick="closeOrderDetails()" class="px-4 py-2 bg-pink-600 text-white rounded">Close</button>
      </div>
    </div>
  </div>

  <!-- Cancel Confirmation Modal -->
  <div id="cancelConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm text-center">
      <h2 class="text-lg font-bold mb-4 text-red-600">Cancel Order?</h2>
      <p class="text-gray-700 mb-6">Are you sure you want to cancel this order? This action cannot be undone.</p>
      <div class="flex justify-center gap-4">
        <button onclick="closeCancelModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">No, Go Back</button>
        <button onclick="confirmCancelOrder()" class="px-4 py-2 bg-red-600 text-white rounded">Yes, Cancel</button>
      </div>
    </div>
  </div>

  <script>
    function viewOrderDetails(orderId) {
      fetch("myorders.php?fetch_order=1&order_id=" + orderId)
        .then(res => res.text())
        .then(html => {
          document.getElementById("orderDetailsContent").innerHTML = html;
          document.getElementById("orderDetailsModal").classList.remove("hidden");
          document.getElementById("orderDetailsModal").classList.add("flex");
        });
    }

    function closeOrderDetails() {
      document.getElementById("orderDetailsModal").classList.add("hidden");
    }

    let pendingCancelOrderId = null;

    function cancelOrder(orderId) {
      pendingCancelOrderId = orderId;
      document.getElementById("cancelConfirmModal").classList.remove("hidden");
      document.getElementById("cancelConfirmModal").classList.add("flex");
    }

    function closeCancelModal() {
      document.getElementById("cancelConfirmModal").classList.add("hidden");
      pendingCancelOrderId = null;
    }

    function confirmCancelOrder() {
      if (!pendingCancelOrderId) return;

      fetch('myorders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'cancel_order=' + encodeURIComponent(pendingCancelOrderId)
      })
        .then(res => res.text())
        .then(response => {
          if (response === "success") {
            // Hide the cancel button
            document.getElementById('cancel-section-' + pendingCancelOrderId)?.classList.add('hidden');

            // Update the status badge
            const statusCell = document.getElementById('status-' + pendingCancelOrderId);
            if (statusCell) {
              statusCell.innerHTML = '<span class="text-white text-xs px-2 py-1 rounded-full bg-red-500">Cancelled</span>';
            }
          } else {
            alert("❌ " + response);
          }
          closeCancelModal();
        })
        .catch(err => {
          alert("An error occurred.");
          closeCancelModal();
        });
    }
  </script>
  <?php include("footer.php"); ?>
</body>

</html>