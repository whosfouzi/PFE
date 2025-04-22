<?php
$total=0;
session_start();
if(isset($_POST["add_to_cart"]))
{
  if(isset($_SESSION["shopping_cart"]))
  {
    $item_array_id = array_column($_SESSION["shopping_cart"], "item_id"); 
    $item_array_category = array_column($_SESSION["shopping_cart"], "item_category");
    if (!in_array($_GET["id"], $item_array_id)) {
      $count1 = count($_SESSION["shopping_cart"]);
      $item_array = array(
        'item_id' => $_GET["id"],
        'item_name' => $_POST["hidden_name"],
        'item_price' => $_POST["hidden_price"],
        'item_image' => $_POST["hidden_img_id"],
        'item_category' => $_POST["hidden_category"],
        'item_quantity' => $_POST["item_quantity"]
      );
      $_SESSION["shopping_cart"][$count1] = $item_array;
    } else {
      foreach ($_SESSION["shopping_cart"] as $keys => $values) {
        if ($values["item_id"] == $_GET["id"]) {
          $item_array = array(
            'item_id' => $_GET["id"],
            'item_name' => $_POST["hidden_name"],
            'item_price' => $_POST["hidden_price"],
            'item_image' => $_POST["hidden_img_id"],
            'item_category' => $_POST["hidden_category"],
            'item_quantity' => $_POST["item_quantity"]  // ✅ Respect quantity from input
          );
          $_SESSION["shopping_cart"]["$keys"] = $item_array;
        }
      }
    }
    
  }
  else
  {
    $item_array=array(
      'item_id' => $_GET["id"],
      'item_name' => $_POST["hidden_name"],
      'item_price' => $_POST["hidden_price"],
      'item_category' => $_POST["hidden_category"],
      'item_image' => $_POST["hidden_img_id"],
      'item_quantity' => 1
    );
    $_SESSION["shopping_cart"][0] = $item_array;
  }
}
if(isset($_GET["action"]) && $_GET["action"]=="delete")
{
  foreach ($_SESSION["shopping_cart"] as $keys => $values)
  {
    if($values["item_id"]==$_GET["id"])
    {
      unset($_SESSION["shopping_cart"][$keys]);
      header("Location: shopping_cart.php");
      exit();
    }
  }
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

<div class="container mx-auto px-4 py-10">
  <h2 class="text-3xl font-bold mb-6 text-center text-gray-800">🛒 Your Cart</h2>

  <?php if (!empty($_SESSION['shopping_cart'])): ?>
  <div class="overflow-x-auto bg-white shadow rounded-lg">
    <table class="min-w-full text-sm text-left">
      <thead class="bg-pink-100 text-pink-700">
        <tr>
          <th class="px-6 py-3">Product</th>
          <th class="px-6 py-3">Category</th>
          <th class="px-6 py-3">Price</th>
          <th class="px-6 py-3">Quantity</th>
          <th class="px-6 py-3">Total</th>
          <th class="px-6 py-3">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php
        $total = 0;
        foreach ($_SESSION["shopping_cart"] as $item):
          $item_total = $item["item_quantity"] * $item["item_price"];
          $total += $item_total;
        ?>
        <tr>
          <td class="px-6 py-4"><?= htmlspecialchars($item["item_name"]) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($item["item_category"]) ?></td>
          <td class="px-6 py-4">€<?= number_format($item["item_price"], 2) ?></td>
          <td class="px-6 py-4"><?= $item["item_quantity"] ?></td>
          <td class="px-6 py-4">€<?= number_format($item_total, 2) ?></td>
          <td class="px-6 py-4">
            <a href="shopping_cart.php?action=delete&id=<?= $item["item_id"] ?>"
               class="text-red-600 hover:text-red-800 font-medium">Remove</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr class="bg-pink-50 font-semibold">
          <td colspan="4" class="px-6 py-4 text-right">Total:</td>
          <td class="px-6 py-4">€<?= number_format($total, 2) ?></td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="flex justify-end mt-6">
    <a href="checkout.php" class="bg-pink-600 text-white px-6 py-3 rounded hover:bg-pink-700 shadow">
      Proceed to Checkout
    </a>
  </div>

  <?php else: ?>
  <div class="bg-white p-6 rounded-lg shadow text-center">
    <p class="text-lg font-semibold text-gray-600">Your cart is empty 🛍️</p>
    <a href="index.php" class="mt-4 inline-block bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700">Back to Shop</a>
  </div>
  <?php endif; ?>
</div>

<?php include("footer.php"); ?>
</body>
</html>
