<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['order_id']) || !isset($_POST['order_status'])) {
    echo "missing_data";
    exit();
  }

  $order_id = intval($_POST['order_id']);
  $status = $_POST['order_status'];

  $valid_statuses = ['processing', 'shipped', 'cancelled'];
  if (!in_array($status, $valid_statuses)) {
    echo "invalid_status";
    exit();
  }

  $conn = new mysqli("localhost", "root", "", "giftstore");
  $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
  $stmt->bind_param("si", $status, $order_id);

  if ($stmt->execute()) {
    echo "success";
  } else {
    echo "fail";
  }
  exit();
}
echo "invalid_request";
?>
