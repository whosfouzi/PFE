<?php
if (!isset($_GET['id'])) {
  echo "Invalid request.";
  exit();
}

$order_id = intval($_GET['id']);
$conn = new mysqli("localhost", "root", "", "giftstore");

$stmt = $conn->prepare("SELECT product_name, quantity, unit_price FROM order_items WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "<p>No items found for this order.</p>";
  exit();
}
?>
<table class="min-w-full text-sm text-left border">
  <thead class="bg-pink-100 text-pink-700">
    <tr>
      <th class="px-4 py-2">Product</th>
      <th class="px-4 py-2">Quantity</th>
      <th class="px-4 py-2">Unit Price</th>
      <th class="px-4 py-2">Subtotal</th>
    </tr>
  </thead>
  <tbody class="divide-y divide-gray-200">
    <?php while ($item = $result->fetch_assoc()): ?>
      <tr>
        <td class="px-4 py-2"><?= htmlspecialchars($item['product_name']) ?></td>
        <td class="px-4 py-2"><?= $item['quantity'] ?></td>
        <td class="px-4 py-2">€<?= number_format($item['unit_price'], 2) ?></td>
        <td class="px-4 py-2">€<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>