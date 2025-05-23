<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "giftstore");
if ($conn->connect_error) {
  die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Ensure user is logged in
if (!isset($_SESSION["id"])) {
  $_SESSION['redirect_after_login'] = 'checkout.php';
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['id'];
$email = $_POST['email'] ?? ($_SESSION['email'] ?? '');

// Fetch user's existing details from the database for pre-filling the form
$user_details = [];
// REMOVED 'address' from this SELECT, as per user's clarification that 'users' table has no address column.
$stmt_user = $conn->prepare("SELECT email, fname, lname, phone FROM users WHERE id = ?");
if ($stmt_user) {
  $stmt_user->bind_param("i", $user_id);
  $stmt_user->execute();
  $result_user = $stmt_user->get_result();
  if ($result_user->num_rows > 0) {
    $user_details = $result_user->fetch_assoc();
  }
  $stmt_user->close();
}

// Shipping Options (You can fetch these from a database table if more complex)
$shipping_options = [
  'none' => ['name' => 'No Shipping (Pickup)', 'cost' => 0.00], // New "No Shipping" option
  'standard' => ['name' => 'Standard Shipping (5-7 days)', 'cost' => 500.00],
  'express' => ['name' => 'Express Shipping (1-2 days)', 'cost' => 1500.00]
];

// Initialize selected_shipping_cost to 0 (for initial display before form submission)
$selected_shipping_cost = 0;
$selected_shipping_method_key = 'none'; // Default selected method

// Initialize shopping cart and calculate subtotal
$shopping_cart = $_SESSION["shopping_cart"] ?? [];
$subtotal = 0;
foreach ($shopping_cart as $item) {
  $subtotal += $item["item_price"] * $item["item_quantity"];
}

$total = $subtotal + $selected_shipping_cost; // Initial total before form submission

// Handle form submission
if (isset($_POST['submit']) && !empty($shopping_cart)) {
  // Start transaction
  $conn->autocommit(false);

  try {
    // Validate stock first
    foreach ($shopping_cart as $item) {
      $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
      $stmt->bind_param("i", $item['item_id']);
      $stmt->execute();
      $stmt->bind_result($current_stock);
      $stmt->fetch();
      $stmt->close();

      if ($current_stock < $item['item_quantity']) {
        throw new Exception("Not enough stock for {$item['item_name']}. Available: $current_stock");
      }
    }

    // Process order if validation passed
    $fname = $_POST["fname"] ?? $user_details['fname'] ?? '';
    $lname = $_POST["lname"] ?? $user_details['lname'] ?? '';
    $phone = $_POST["phone"] ?? $user_details['phone'] ?? '';
    // Address is now ONLY taken from POST, as it's not in user_details from DB
    $address = $_POST["address"] ?? '';
    $full_name = trim("$fname $lname");

    // Get selected shipping method from POST, default to 'none' if not set or invalid
    $selected_shipping_method_key = $_POST['shipping_method'] ?? 'none';
    $selected_shipping_cost = $shipping_options[$selected_shipping_method_key]['cost'] ?? 0; // Ensure cost is 0 if method is invalid

    $payment_method = $_POST['payment_method'] ?? 'cod'; // Default to Cash on Delivery

    // Recalculate total with selected shipping
    $total = $subtotal + $selected_shipping_cost;

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders
            (user_id, user_name, email, phone, address, total_price, order_status)
            VALUES (?, ?, ?, ?, ?, ?, 'processing')");
    // CORRECTED: Changed 'd' (double) for address to 's' (string) and 's' for total_price to 'd' (double)
    // The type string 'issssd' matches:
    // i: user_id (integer)
    // s: user_name (string)
    // s: email (string)
    // s: phone (string)
    // s: address (string)
    // d: total_price (double)
    $stmt->bind_param("issssd", $user_id, $full_name, $email, $phone, $address, $total);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
    foreach ($shopping_cart as $item) {
      $stmt->bind_param("iisid", $order_id, $item['item_id'], $item['item_name'], $item['item_quantity'], $item['item_price']);
      $stmt->execute();
    }
    $stmt->close();

    // Decrement stock
    foreach ($shopping_cart as $item) {
      $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
      $stmt->bind_param("ii", $item['item_quantity'], $item['item_id']);
      $stmt->execute();
      $stmt->close();
    }

    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Clear cart items associated with this cart
    $stmt = $conn->prepare("DELETE ci FROM cart_items ci JOIN cart c ON ci.cart_id = c.id WHERE c.user_id = ?");
    if (!$stmt) {
      error_log("Failed to prepare statement for clearing cart items: " . $conn->error);
    } else {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();
    }

    $conn->commit();
    unset($_SESSION["shopping_cart"]); // Clear session cart

    // Pass order_id to thankyou.php
    header("Location: thankyou.php?order_id=" . $order_id);
    exit();

  } catch (Exception $e) {
    $conn->rollback();
    $error_message = $e->getMessage();
    die("Checkout failed: " . $error_message);
  } finally {
    $conn->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>GiftStore - Checkout</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
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

    .focus\:border-turquoise-primary:focus {
      border-color: #56c8d8;
    }

    .focus\:ring-turquoise-primary:focus {
      --tw-ring-color: #56c8d8;
    }
  </style>
  <script>
    // JavaScript for client-side validation and live total update
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.querySelector('form');
      const firstNameInput = document.querySelector('input[name="fname"]');
      const lastNameInput = document.querySelector('input[name="lname"]');
      const emailInput = document.querySelector('input[name="email"]');
      const phoneInput = document.querySelector('input[name="phone"]');
      const addressTextarea = document.querySelector('textarea[name="address"]');
      const shippingMethodRadios = document.querySelectorAll('input[name="shipping_method"]');
      const subtotalElement = document.getElementById('subtotal-display');
      const shippingCostElement = document.getElementById('shipping-cost-display');
      const totalElement = document.getElementById('total-display');

      const shippingCosts = {
        <?php foreach ($shipping_options as $key => $option): ?>
                      '<?= $key ?>': <?= $option['cost'] ?>,
        <?php endforeach; ?>};

      function updateOrderSummary() {
        let currentShippingCost = 0;
        shippingMethodRadios.forEach(radio => {
          if (radio.checked) {
            currentShippingCost = shippingCosts[radio.value];
          }
        });

        const subtotal = parseFloat(subtotalElement.dataset.subtotal); // Get actual subtotal
        const total = subtotal + currentShippingCost;

        shippingCostElement.textContent = `DA ${currentShippingCost.toFixed(2)}`;
        totalElement.textContent = `DA ${total.toFixed(2)}`;
      }

      // Initial update
      updateOrderSummary();

      // Event listeners for shipping method change
      shippingMethodRadios.forEach(radio => {
        radio.addEventListener('change', updateOrderSummary);
      });

      // Basic client-side validation (can be expanded)
      form.addEventListener('submit', function (event) {
        let isValid = true;

        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('input, textarea').forEach(el => el.classList.remove('border-red-500'));

        if (!firstNameInput.value.trim()) {
          displayError(firstNameInput, 'First Name is required.');
          isValid = false;
        }
        if (!lastNameInput.value.trim()) {
          displayError(lastNameInput, 'Last Name is required.');
          isValid = false;
        }
        if (!emailInput.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
          displayError(emailInput, 'Valid Email is required.');
          isValid = false;
        }
        if (!phoneInput.value.trim() || !/^\d{10}$/.test(phoneInput.value)) {
          displayError(phoneInput, 'Valid 10-digit Phone is required.');
          isValid = false;
        }
        if (!addressTextarea.value.trim()) {
          displayError(addressTextarea, 'Address is required.');
          isValid = false;
        }

        if (!isValid) {
          event.preventDefault(); // Stop form submission
          alert('Please correct the errors in the form.'); // Simple alert for user
        }
      });

      function displayError(inputElement, message) {
        inputElement.classList.add('border-red-500');
        const errorMessage = document.createElement('p');
        errorMessage.classList.add('text-red-500', 'text-sm', 'mt-1', 'error-message');
        errorMessage.textContent = message;
        inputElement.parentNode.appendChild(errorMessage);
      }
    });
  </script>
</head>

<body class="bg-gray-100 font-sans">

  <?php include("navbar.php"); ?>

  <div class="max-w-6xl mx-auto px-4 py-12 bg-gray-50 min-h-screen">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-10 text-center">Checkout</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
      <div class="lg:col-span-2 bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-turquoise-primary mb-6 border-b pb-4">Shipping & Payment Details</h2>

        <form method="POST" action="checkout.php" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="fname" class="block mb-1 font-semibold text-gray-700">First Name <span
                  class="text-red-500">*</span></label>
              <input type="text" id="fname" name="fname" value="<?= htmlspecialchars($user_details['fname'] ?? '') ?>"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-turquoise-primary focus:border-turquoise-primary">
            </div>

            <div>
              <label for="lname" class="block mb-1 font-semibold text-gray-700">Last Name <span
                  class="text-red-500">*</span></label>
              <input type="text" id="lname" name="lname" value="<?= htmlspecialchars($user_details['lname'] ?? '') ?>"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-turquoise-primary focus:border-turquoise-primary">
            </div>

            <div>
              <label for="email" class="block mb-1 font-semibold text-gray-700">Email <span
                  class="text-red-500">*</span></label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_details['email'] ?? '') ?>"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-turquoise-primary focus:border-turquoise-primary">
            </div>

            <div>
              <label for="phone" class="block mb-1 font-semibold text-gray-700">Phone <span
                  class="text-red-500">*</span></label>
              <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user_details['phone'] ?? '') ?>"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-turquoise-primary focus:border-turquoise-primary">
            </div>
          </div>

          <div class="mt-4">
            <label for="address" class="block mb-1 font-semibold text-gray-700">Address <span
                class="text-red-500">*</span></label>
            <textarea id="address" name="address" rows="3" required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-turquoise-primary focus:border-turquoise-primary"><?= htmlspecialchars($user_details['address'] ?? '') ?></textarea>
          </div>

          <div class="mt-6">
            <label class="block mb-2 font-semibold text-gray-700">Shipping Method <span
                class="text-red-500">*</span></label>
            <div class="space-y-3">
              <?php foreach ($shipping_options as $key => $option): ?>
                <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                  <input type="radio" name="shipping_method" value="<?= $key ?>"
                    class="form-radio h-5 w-5 text-turquoise-primary focus:ring-turquoise-primary"
                    <?= ($key === $selected_shipping_method_key) ? 'checked' : '' ?>>
                  <span class="ml-3 text-gray-800 font-medium"><?= htmlspecialchars($option['name']) ?></span>
                  <span class="ml-auto text-gray-600">DA <?= number_format($option['cost'], 2) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mt-6">
            <label class="block mb-2 font-semibold text-gray-700">Payment Method <span
                class="text-red-500">*</span></label>
            <div class="space-y-3">
              <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                <input type="radio" name="payment_method" value="cod" class="form-radio h-5 w-5 text-turquoise-primary focus:ring-turquoise-primary" checked>
                <span class="ml-3 text-gray-800 font-medium">Cash on Delivery (COD)</span>
              </label>
              </div>
          </div>


          <div class="mt-8 text-right">
            <button type="submit" name="submit"
              class="bg-turquoise-primary hover:bg-cyan-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition-colors shadow-lg">
              Place Order
            </button>
          </div>
        </form>
      </div>

      <div class="lg:col-span-1 bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Order Summary</h2>
        <?php if (!empty($shopping_cart)): ?>
          <ul class="divide-y divide-gray-200">
            <?php foreach ($shopping_cart as $item): ?>
              <li class="flex justify-between items-center py-4">
                <div class="flex items-center">
                  <img src="uploads/<?= htmlspecialchars($item['item_image'] ?? 'no-image.jpg') ?>"
                    alt="<?= htmlspecialchars($item['item_name']) ?>" class="w-16 h-16 object-cover rounded-md mr-4">
                  <div>
                    <h4 class="text-gray-800 font-medium"><?= htmlspecialchars($item['item_name']) ?></h4>
                    <p class="text-sm text-gray-500">Quantity: <?= $item['item_quantity'] ?></p>
                  </div>
                </div>
                <span class="text-turquoise-primary font-semibold">DA
                  <?= number_format($item['item_price'] * $item['item_quantity'], 2) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>

          <div class="mt-6 pt-4 border-t border-gray-200 space-y-3 text-lg">
            <div class="flex justify-between font-medium text-gray-700">
              <span>Subtotal:</span>
              <span id="subtotal-display" data-subtotal="<?= $subtotal ?>" class="text-turquoise-primary">DA
                <?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="flex justify-between font-medium text-gray-700">
              <span>Shipping:</span>
              <span id="shipping-cost-display" class="text-turquoise-primary">DA
                <?= number_format($selected_shipping_cost, 2) ?></span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 text-xl mt-4">
              <span>Total:</span>
              <span id="total-display" class="text-turquoise-primary">DA <?= number_format($total, 2) ?></span>
            </div>
          </div>
        <?php else: ?>
          <p class="text-center text-gray-600">Your cart is empty. Please add items before checking out.</p>
          <div class="mt-6 text-center">
            <a href="index.php"
              class="inline-block bg-turquoise-primary hover:bg-cyan-700 text-white px-6 py-2 rounded-lg transition-colors">
              Continue Shopping
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php include("footer.php"); ?>
</body>

</html>