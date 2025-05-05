<?php
session_start();
if (!isset($_SESSION["userid"])) {
  header("Location: login.php");
  exit();
}
$email = $_SESSION['email'] ?? '';

if (isset($_POST['submit']) && !empty($_SESSION["shopping_cart"])) {
  $conn = new mysqli("localhost", "root", "", "giftstore");

  $fname = $_POST["fname"];
  $lname = $_POST["lname"];
  $phone = $_POST["phone"];
  $address = $_POST["address"];

  $full_name = $fname . ' ' . $lname;

  // Calculate total
  $total = 0;
  foreach ($_SESSION["shopping_cart"] as $item) {
    $total += $item["item_price"] * $item["item_quantity"];
  }

  // Insert into orders
  $user_id = $_SESSION['id']; // assuming this session holds the logged-in user's ID
  $stmt = $conn->prepare("INSERT INTO orders (user_id, user_name, email, phone, address, total_price) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("issssd", $user_id, $full_name, $email, $phone, $address, $total);
  $stmt->execute();
  $order_id = $stmt->insert_id;
  $stmt->close();

  // Insert into order_items
  $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
  foreach ($_SESSION["shopping_cart"] as $item) {
    $product_id = $item["item_id"];
    $name = $item["item_name"];
    $qty = $item["item_quantity"];
    $price = $item["item_price"];
    $stmt->bind_param("iisid", $order_id, $product_id, $name, $qty, $price);
    $stmt->execute();
  }
  $stmt->close();
  

  // Clear cart & redirect
  unset($_SESSION["shopping_cart"]);
  header("Location: thankyou.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>GiftStore - Checkout</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

  <?php include("navbar.php"); ?>

  <div class="max-w-4xl mx-auto px-4 py-10 bg-white rounded shadow mt-10">
    <h2 class="text-2xl font-bold text-pink-600 mb-6">🧾 Checkout Form</h2>

    <form method="POST" action="checkout.php" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block mb-1 font-medium">First Name</label>
        <input type="text" name="fname" required class="w-full px-4 py-2 border rounded">
      </div>

      <div>
        <label class="block mb-1 font-medium">Last Name</label>
        <input type="text" name="lname" required class="w-full px-4 py-2 border rounded">
      </div>

      <div>
        <label class="block mb-1 font-medium">Email</label>
        <input type="email" name="email" required class="w-full px-4 py-2 border rounded">
      </div>

      <div>
        <label class="block mb-1 font-medium">Phone</label>
        <input type="text" name="phone" required class="w-full px-4 py-2 border rounded">
      </div>

      <div class="md:col-span-2">
        <label class="block mb-1 font-medium">Address</label>
        <textarea name="address" rows="2" required class="w-full px-4 py-2 border rounded"></textarea>
      </div>

      <div class="md:col-span-2">
        <h3 class="text-xl font-semibold text-gray-700 mt-6 mb-2">💳 Payment Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Name on Card</label>
            <input type="text" name="nameoncard" required class="w-full px-4 py-2 border rounded">
          </div>
          <div>
            <label class="block mb-1 font-medium">Card Number</label>
            <input type="text" name="Creditcardnumber" required class="w-full px-4 py-2 border rounded">
          </div>
          <div>
            <label class="block mb-1 font-medium">Expiration Month</label>
            <input type="text" name="expmonth" required class="w-full px-4 py-2 border rounded">
          </div>
          <div>
            <label class="block mb-1 font-medium">Expiration Year</label>
            <input type="text" name="expyear" required class="w-full px-4 py-2 border rounded">
          </div>
          <div>
            <label class="block mb-1 font-medium">CVV</label>
            <input type="text" name="cvv" required class="w-full px-4 py-2 border rounded">
          </div>
        </div>
      </div>

      <div class="md:col-span-2 text-right mt-6">
        <button type="submit" name="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2 rounded">
          Submit Order
        </button>
      </div>
    </form>
  </div>

  <?php include("footer.php"); ?>
</body>

</html>