<?php
session_start();

$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

if (isset($_POST["add_to_cart"]) && isset($_SESSION["id"])) {
    $userId = $_SESSION["id"];
    $productId = $_GET["id"];
    $quantity = $_POST["item_quantity"];

    // Fetch stock from DB
    $stock_check = $db->prepare("SELECT stock FROM products WHERE id = ?");
    $stock_check->bind_param("i", $productId);
    $stock_check->execute();
    $stock_check->bind_result($availableStock);
    $stock_check->fetch();
    $stock_check->close();

    if ($quantity > $availableStock) {
        $quantity = $availableStock; // Cap to max stock
    }


    // Check if cart exists for user
    $stmt = $db->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt_insert = $db->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt_insert->bind_param("i", $userId);
        $stmt_insert->execute();
        $cartId = $stmt_insert->insert_id;
        $stmt_insert->close();
    } else {
        $stmt->bind_result($cartId);
        $stmt->fetch();
    }
    $stmt->close();

    // Add or update cart items
    $stmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $cartId, $productId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($itemId, $existingQuantity);
        $stmt->fetch();
        $newQuantity = $quantity;
        $stmt_update = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt_update->bind_param("ii", $newQuantity, $itemId);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        $stmt_insert_item = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt_insert_item->bind_param("iii", $cartId, $productId, $quantity);
        $stmt_insert_item->execute();
        $stmt_insert_item->close();
    }
    $stmt->close();
}

// Fetch cart for display
if (isset($_SESSION['id'])) {
    $stmt = $db->prepare("
        SELECT p.id as item_id, p.name as item_name, p.price as item_price, ci.quantity as item_quantity, p.category as item_category
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.id
        JOIN products p ON ci.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $_SESSION["shopping_cart"] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $_SESSION["shopping_cart"] = [];
}

// Handle remove from cart
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && isset($_SESSION['id'])) {
  $productId = $_GET['id'];
  $userId = $_SESSION['id'];

  // Find the user's cart
  $stmt = $db->prepare("SELECT id FROM cart WHERE user_id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->bind_result($cartId);
  $stmt->fetch();
  $stmt->close();

  if ($cartId) {
      // Delete the product from cart_items
      $del = $db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
      $del->bind_param("ii", $cartId, $productId);
      $del->execute();
      $del->close();
  }

  header("Location: shopping_cart.php");
  exit();
}

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>GiftStore - Shopping Cart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include("navbar.php"); ?>

<div class="container" style="margin: 40px auto; padding: 20px;">
  <h2 class="center-align" style="font-size: 2.2rem; font-weight: bold; margin-bottom: 30px; color: #333;">
    🛒 Your Cart
  </h2>

  <?php if (!empty($_SESSION['shopping_cart'])): ?>
    <div class="card white z-depth-1" style="border-radius: 12px; overflow: hidden; padding: 30px;">
      <table class="highlight responsive-table">
        <thead style="background-color: #56c8d8; color: white;">
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $total = 0;
          foreach ($_SESSION["shopping_cart"] as $item):
            $item_total = $item["item_quantity"] * $item["item_price"];
            $total += $item_total;
          ?>
            <tr>
              <td><?= htmlspecialchars($item["item_name"]) ?></td>
              <td><?= htmlspecialchars($item["item_category"]) ?></td>
              <td>€<?= number_format($item["item_price"], 2) ?></td>
              <td><?= (int)$item["item_quantity"] ?></td>
              <td>€<?= number_format($item_total, 2) ?></td>
              <td>
                <a href="shopping_cart.php?action=delete&id=<?= $item["item_id"] ?>" 
                   class="red-text text-darken-1" style="font-weight: 600;">Remove</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php // Clear the shopping cart
unset($_SESSION["shopping_cart"]);
?>
          <tr style="font-weight: bold;">
            <td colspan="4" class="right-align">Total:</td>
            <td>€<?= number_format($total, 2) ?></td>
            <td></td>
          </tr>
        </tbody>
      </table>

      <div class="right-align" style="margin-top: 30px;">
        <a href="checkout.php" class="btn" style="background-color: #c0392b; font-weight: 600;">
          Proceed to Checkout
        </a>
      </div>
    </div>

  <?php else: ?>
    <div class="row">
      <div class="col s12">
        <section class="empty-cart-wrap" style="background-image: url('https://sugarwish.com/images/empty-cart-bg.png'); background-size: cover; background-position: center; padding: 100px 20px; border-radius: 12px; text-align: center;">
          <p style="font-size: 24px; font-weight: 600; color: #333;">
            Hold your horses!<br>
            You haven’t added anything to your cart.
          </p>
          <div style="margin-top: 20px;">
            <a href="products.php" class="btn white red-text text-darken-2 z-depth-0" style="border: 2px solid #c0392b; font-weight: 600;">
              Get Started
            </a>
          </div>
        </section>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include("footer.php"); ?>
</body>
</html>
