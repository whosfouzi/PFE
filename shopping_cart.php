<?php
session_start();

$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$db->set_charset("utf8mb4");

// Handle adding to cart (existing logic, kept for consistency)
if (isset($_POST["add_to_cart"]) && isset($_SESSION["id"])) {
    $userId = $_SESSION["id"];
    $productId = $_GET["id"]; // Assuming product ID is passed via GET for add_to_cart
    $quantity = $_POST["item_quantity"];

    // Fetch stock from DB
    $stock_check = $db->prepare("SELECT stock FROM products WHERE id = ?");
    $stock_check->bind_param("i", $productId);
    $stock_check->execute();
    $stock_check->bind_result($availableStock);
    $stock_check->fetch();
    $stock_check->close();

    // Ensure cart exists for user
    $cartId = null;
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

    // Determine the actual quantity to be set, capped by available stock
    $finalQuantity = min($quantity, $availableStock);

    // Check if item already exists in cart_items
    $itemId = null;
    $stmt_check_item = $db->prepare("SELECT id FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt_check_item->bind_param("ii", $cartId, $productId);
    $stmt_check_item->execute();
    $stmt_check_item->store_result();

    if ($stmt_check_item->num_rows > 0) {
        $stmt_check_item->bind_result($itemId);
        $stmt_check_item->fetch();
        // Item exists, update its quantity to the finalQuantity
        if ($finalQuantity > 0) {
            $stmt_update = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $finalQuantity, $itemId);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // If finalQuantity is 0, remove the item from cart
            $del = $db->prepare("DELETE FROM cart_items WHERE id = ?");
            $del->bind_param("i", $itemId);
            $del->execute();
            $del->close();
        }
    } else {
        // Item does not exist, insert it with the finalQuantity
        if ($finalQuantity > 0) { // Only insert if quantity is positive
            $stmt_insert_item = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt_insert_item->bind_param("iii", $cartId, $productId, $finalQuantity);
            $stmt_insert_item->execute();
            $stmt_insert_item->close();
        }
    }
    $stmt_check_item->close();

    header("Location: shopping_cart.php"); // Redirect to prevent refresh duplication
    exit();
}

// Handle remove from cart (now via AJAX, but keeping GET fallback)
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

// Handle quantity update from form submission
if (isset($_POST['update_quantity']) && isset($_SESSION['id'])) {
    $productIdToUpdate = $_POST['product_id'];
    $newQuantity = intval($_POST['new_quantity']);
    $userId = $_SESSION['id'];

    // Fetch available stock for the product
    $stock_check = $db->prepare("SELECT stock FROM products WHERE id = ?");
    $stock_check->bind_param("i", $productIdToUpdate);
    $stock_check->execute();
    $stock_check->bind_result($availableStock);
    $stock_check->fetch();
    $stock_check->close();

    // Cap new quantity at available stock
    $finalNewQuantity = min($newQuantity, $availableStock);
    if ($finalNewQuantity < 0) $finalNewQuantity = 0; // Ensure quantity is not negative

    // Get cart ID
    $cartId = null;
    $stmt = $db->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($cartId);
    $stmt->fetch();
    $stmt->close();

    if ($cartId) {
        if ($finalNewQuantity > 0) {
            // Update quantity in cart_items
            $stmt_update = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
            $stmt_update->bind_param("iii", $finalNewQuantity, $cartId, $productIdToUpdate);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // Remove item if quantity is 0
            $del = $db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
            $del->bind_param("ii", $cartId, $productIdToUpdate);
            $del->execute();
            $del->close();
        }
    }
    header("Location: shopping_cart.php"); // Redirect after update
    exit();
}


// Fetch cart for display
$_SESSION["shopping_cart"] = []; // Initialize to empty array
$total = 0; // Initialize total
if (isset($_SESSION['id'])) {
    $stmt = $db->prepare("
        SELECT p.id as item_id, p.name as item_name, p.price as item_price, ci.quantity as item_quantity, p.gift_category as item_category, p.image as item_image, p.stock as item_stock
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

    // Calculate total
    foreach ($_SESSION["shopping_cart"] as $item) {
        $total += $item["item_quantity"] * $item["item_price"];
    }
}

// Close DB connection if it's still open (after all operations)
$db->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>GiftStore - Your Magical Cart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Pacifico&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Custom CSS Variables for Themed Colors */
    :root {
      --primary-turquoise: #56c8d8;
      --primary-dark-turquoise: #45b1c0;
      --accent-red: #ef4444;
      --accent-dark-red: #dc2626;
      --light-blue-bg-start: #e0f7fa;
      --light-blue-bg-end: #b2ebf2;
      --dark-blue-bg: #80deea;
      --text-dark: #333;
      --card-bg: rgba(255, 255, 255, 0.9); /* Slightly more opaque */
      --card-border: rgba(255, 255, 255, 0.7);
      --shadow-color: rgba(0, 0, 0, 0.1);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, var(--light-blue-bg-start) 0%, var(--light-blue-bg-end) 50%, var(--dark-blue-bg) 100%);
      color: var(--text-dark);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden; /* Prevent horizontal scroll */
    }
    .pacifico-font { font-family: 'Pacifico', cursive; }

    /* General Button Styles */
    .btn-base {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      font-weight: 600;
      padding: 0.75rem 1.5rem;
      border-radius: 0.75rem; /* More rounded */
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px var(--shadow-color);
      cursor: pointer;
    }
    .btn-primary {
      background-color: var(--accent-red);
      color: white;
    }
    .btn-primary:hover {
      background-color: var(--accent-dark-red);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(239, 68, 68, 0.3);
    }
    .btn-secondary {
      background-color: var(--primary-turquoise);
      color: white;
    }
    .btn-secondary:hover {
      background-color: var(--primary-dark-turquoise);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(86, 200, 216, 0.3);
    }
    .btn-outline {
      background-color: transparent;
      color: var(--primary-turquoise);
      border: 2px solid var(--primary-turquoise);
      box-shadow: none;
    }
    .btn-outline:hover {
      background-color: var(--primary-turquoise);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(86, 200, 216, 0.2);
    }
    .btn-icon-only {
        padding: 0.5rem;
        border-radius: 50%;
        width: 2.5rem;
        height: 2.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* Main Content Card */
    .main-content-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 1.5rem;
      box-shadow: 0 10px 30px var(--shadow-color);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      padding: 2rem;
    }

    /* Individual Cart Item Card */
    .cart-item-card {
        display: flex;
        flex-direction: column;
        background-color: #ffffff;
        border-radius: 1rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        border: 1px solid #e2e8f0;
    }
    .cart-item-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    }
    .cart-item-card:last-child {
        margin-bottom: 0;
    }

    /* Quantity Control within item card */
    .quantity-control {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        background-color: rgba(255,255,255,0.7);
        border-radius: 0.5rem;
        border: 1px solid rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .quantity-control button {
        background-color: var(--primary-turquoise);
        color: white;
        border-radius: 0.5rem;
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 700;
        transition: background-color 0.2s ease;
        flex-shrink: 0;
    }
    .quantity-control button:hover {
        background-color: var(--primary-dark-turquoise);
    }
    .quantity-control input {
        width: 2.5rem; /* Smaller input */
        text-align: center;
        border: none;
        background-color: transparent;
        font-weight: 600;
        color: var(--text-dark);
        -moz-appearance: textfield;
        padding: 0.25rem;
    }
    .quantity-control input::-webkit-outer-spin-button,
    .quantity-control input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Empty Cart Section */
    .empty-cart-section {
        background-image: url('https://placehold.co/1200x400/E0F7FA/56C8D8?text=Your+Cart+is+Empty');
        background-size: cover;
        background-position: center;
        padding: 6rem 1.5rem;
        border-radius: 1.5rem;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 400px;
        color: var(--text-dark);
        position: relative;
        overflow: hidden;
    }
    .empty-cart-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.6);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        z-index: 1;
    }
    .empty-cart-section > * {
        position: relative;
        z-index: 2;
    }
    .empty-cart-section p {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1.4;
        margin-bottom: 1.5rem;
        text-shadow: 0 1px 3px rgba(255,255,255,0.7);
        color: #333;
    }
    .empty-cart-section .btn-get-started {
        background-color: var(--accent-red);
        color: white;
        font-weight: 700;
        padding: 1rem 2.5rem;
        border-radius: 9999px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 15px rgba(239, 68, 68, 0.3);
    }
    .empty-cart-section .btn-get-started:hover {
        background-color: var(--accent-dark-red);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
    }

    /* Message Box (for success/error) */
    .message-box {
        position: fixed;
        top: 1.5rem;
        right: 1.5rem;
        z-index: 1000;
        padding: 1rem 1.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transform: translateX(120%);
        opacity: 0;
        transition: transform 0.5s ease-out, opacity 0.5s ease-out;
    }
    .message-box.show {
        transform: translateX(0);
        opacity: 1;
    }
    .message-box.success {
        background-color: #d1fae5; /* green-100 */
        color: #065f46; /* green-800 */
        border-left: 4px solid #34d399; /* green-500 */
    }
    .message-box.error {
        background-color: #fee2e2; /* red-100 */
        color: #991b1b; /* red-800 */
        border-left: 4px solid #ef4444; /* red-500 */
    }

    /* Custom Confirmation Modal Styles */
    .custom-confirm-modal-backdrop {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }
    .custom-confirm-modal-backdrop.show {
        opacity: 1;
    }
    .custom-confirm-modal-content {
        background: linear-gradient(160deg, #ffffff, #f8f8f8);
        border-radius: 1.5rem;
        box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        transform: scale(0.9);
        opacity: 0;
        transition: transform 0.4s ease-out, opacity 0.4s ease-out;
        padding: 2rem;
        width: 90%;
        max-width: 400px;
        text-align: center;
    }
    .custom-confirm-modal-backdrop.show .custom-confirm-modal-content {
        transform: scale(1);
        opacity: 1;
    }
    .custom-confirm-modal-content h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ef4444; /* Accent red */
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .custom-confirm-modal-content p {
        color: #4a5568;
        margin-bottom: 1.5rem;
    }
  </style>
</head>
<body class="font-sans">

<?php include("navbar.php"); ?>

<main class="flex-grow container mx-auto px-4 py-8 md:py-12">
  <h2 class="text-center text-4xl font-bold text-gray-800 mb-8 md:mb-12 pacifico-font">
    🛒 Your Magical Cart
  </h2>

  <?php if (!empty($_SESSION['shopping_cart'])): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2">
        <div id="cart-items-container" class="space-y-4">
          <?php foreach ($_SESSION["shopping_cart"] as $item): ?>
            <div class="cart-item-card"
                 data-item-id="<?= $item['item_id'] ?>"
                 data-item-price="<?= $item['item_price'] ?>"
                 data-item-stock="<?= $item['item_stock'] ?>">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                    <img src="uploads/<?= htmlspecialchars($item["item_image"] ?? 'https://placehold.co/100x100/E0F7FA/56C8D8?text=Gift') ?>"
                         alt="<?= htmlspecialchars($item["item_name"]) ?>"
                         class="w-24 h-24 object-cover rounded-lg shadow-md border border-gray-100 flex-shrink-0">
                    <div class="flex-grow text-center sm:text-left">
                        <h3 class="text-xl font-semibold text-gray-800 mb-1"><?= htmlspecialchars($item["item_name"]) ?></h3>
                        <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($item["item_category"]) ?></p>
                        <p class="text-primary-turquoise font-bold text-lg">DA<?= number_format($item["item_price"], 2) ?></p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between mt-4 border-t border-gray-100 pt-4 sm:pt-0 sm:border-t-0">
                    <div class="flex items-center gap-4 mb-4 sm:mb-0">
                        <div class="quantity-control">
                            <button class="quantity-btn" data-change="-1" aria-label="Decrease quantity">-</button>
                            <input type="number"
                                   class="quantity-input"
                                   value="<?= (int)$item["item_quantity"] ?>"
                                   min="1"
                                   max="<?= (int)$item["item_stock"] ?>">
                            <button class="quantity-btn" data-change="1" aria-label="Increase quantity">+</button>
                        </div>
                        <span class="font-bold text-gray-800 text-xl">DA<span class="item-subtotal"><?= number_format($item["item_quantity"] * $item["item_price"], 2) ?></span></span>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <button class="btn-base btn-primary text-sm px-3 py-1.5 remove-item-btn">
                            <i class="fas fa-trash-alt"></i> Remove
                        </button>
                        <button class="btn-base btn-outline text-sm px-3 py-1.5 move-to-likes-btn">
                            <i class="fas fa-heart"></i> Move to Likes
                        </button>
                    </div>
                </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="lg:col-span-1">
        <div class="main-content-card p-6 sticky top-8">
          <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4 border-gray-200">Order Summary</h3>
          <div class="space-y-4 text-lg">
            <div class="flex justify-between">
              <span>Subtotal:</span>
              <span class="font-semibold" id="cart-subtotal">DA<?= number_format($total, 2) ?></span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>Shipping:</span>
              <span class="font-semibold">Calculated at Checkout</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>Tax:</span>
              <span class="font-semibold">Included</span>
            </div>
            <div class="flex justify-between pt-4 border-t border-gray-200 text-2xl font-bold text-gray-900">
              <span>Grand Total:</span>
              <span id="cart-grand-total">DA<?= number_format($total, 2) ?></span>
            </div>
          </div>

          <div class="flex flex-col gap-4 mt-8">
            <a href="checkout.php" class="btn-base btn-primary w-full">
              Proceed to Checkout <i class="fas fa-arrow-right ml-2"></i>
            </a>
            <button onclick="showCustomConfirm('Are you sure you want to clear your entire cart? This action cannot be undone.', clearCartConfirmed)" class="btn-base btn-outline w-full">
              <i class="fas fa-times-circle"></i> Clear Cart
            </button>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <div class="empty-cart-section">
      <i class="fas fa-box-open text-primary-turquoise text-7xl mb-6 drop-shadow-lg"></i>
      <p class="text-3xl font-bold text-gray-800 mb-4">
        Your cart feels a little empty...<br>
        Let's fill it with some magic!
      </p>
      <div class="mt-8">
        <a href="products.php" class="btn-base btn-get-started">
          Discover Gifts <i class="fas fa-gift ml-2"></i>
        </a>
      </div>
    </div>
  <?php endif; ?>
</main>

<?php include("footer.php"); ?>

<div id="message-box-container"></div>
<div id="custom-confirm-modal-container"></div>

<script>
    // Function to show custom message boxes (Success/Error toasts)
    function showMessage(message, type = 'success') {
        const container = document.getElementById('message-box-container');
        const messageBox = document.createElement('div');
        messageBox.className = `message-box ${type}`;
        messageBox.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        container.appendChild(messageBox);

        // Animate in
        setTimeout(() => {
            messageBox.classList.add('show');
        }, 50);

        // Animate out and remove
        setTimeout(() => {
            messageBox.classList.remove('show');
            messageBox.addEventListener('transitionend', () => messageBox.remove());
        }, 3000); // Message disappears after 3 seconds
    }

    // Function to show custom confirmation modal
    function showCustomConfirm(message, onConfirmCallback) {
        const container = document.getElementById('custom-confirm-modal-container');
        // Remove any existing confirm modal to prevent duplicates
        if (container.querySelector('.custom-confirm-modal-backdrop')) {
            container.innerHTML = '';
        }

        const confirmModal = document.createElement('div');
        confirmModal.className = 'custom-confirm-modal-backdrop';
        confirmModal.innerHTML = `
            <div class="custom-confirm-modal-content">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Action</h3>
                <p>${message}</p>
                <div class="flex justify-center gap-4 mt-6">
                    <button id="confirmCancelBtn" class="btn-base btn-secondary">Cancel</button>
                    <button id="confirmOkBtn" class="btn-base btn-primary">Confirm</button>
                </div>
            </div>
        `;
        container.appendChild(confirmModal);

        // Animate in
        setTimeout(() => {
            confirmModal.classList.add('show');
        }, 50);

        const closeConfirmModal = () => {
            confirmModal.classList.remove('show');
            confirmModal.addEventListener('transitionend', function handler() {
                confirmModal.remove();
                confirmModal.removeEventListener('transitionend', handler);
            });
        };

        document.getElementById('confirmCancelBtn').addEventListener('click', closeConfirmModal);
        document.getElementById('confirmOkBtn').addEventListener('click', () => {
            onConfirmCallback();
            closeConfirmModal();
        });

        // Close if clicking outside the content
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) {
                closeConfirmModal();
            }
        });
    }


    // Function to update item quantity via AJAX
    async function updateQuantity(itemId, change, directValue = null) {
        const itemCard = document.querySelector(`.cart-item-card[data-item-id="${itemId}"]`);
        if (!itemCard) return;

        const quantityInput = itemCard.querySelector('.quantity-input');
        const currentQuantity = parseInt(quantityInput.value);
        const itemPrice = parseFloat(itemCard.dataset.itemPrice);
        const itemStock = parseInt(itemCard.dataset.itemStock);
        let newQuantity;

        if (directValue !== null) {
            newQuantity = parseInt(directValue);
        } else {
            newQuantity = currentQuantity + change;
        }

        // Validate quantity
        if (isNaN(newQuantity) || newQuantity < 0) {
            newQuantity = 0;
        }
        if (newQuantity > itemStock) {
            newQuantity = itemStock;
            showMessage(`Maximum stock for this item is ${itemStock}.`, 'error');
        }
        // If decrementing from 1, prompt for removal. Otherwise, ensure min quantity is 1 for display.
        if (newQuantity < 1) {
            if (change === -1 && currentQuantity === 1) {
                showCustomConfirm('Setting quantity to 0 will remove this item from your cart. Do you want to remove it?', () => removeItemConfirmed(itemId));
                return; // Stop here, wait for confirm
            }
            newQuantity = 1; // Ensure quantity doesn't go below 1 in input unless confirmed for removal
        }


        if (newQuantity === currentQuantity && directValue === null) {
            return; // No change needed if quantity is the same and not a direct input
        }

        // Update input value immediately for responsiveness
        quantityInput.value = newQuantity;
        const subtotalSpan = itemCard.querySelector('.item-subtotal');
        subtotalSpan.textContent = (newQuantity * itemPrice).toFixed(2);

        // Perform AJAX update
        try {
            const response = await fetch('update_cart_quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}&quantity=${newQuantity}`
            });

            const data = await response.json();

            if (data.success) {
                updateCartTotals(); // Recalculate and update overall totals
                if (newQuantity === 0) {
                    // If quantity becomes 0, remove the item row from display
                    itemCard.remove();
                    showMessage('Item removed from cart.', 'success');
                    // Check if cart is empty after removal
                    if (document.querySelectorAll('#cart-items-container .cart-item-card').length === 0) {
                        window.location.reload(); // Reload to show empty cart state
                    }
                } else {
                    showMessage(data.message, 'success');
                }
            } else {
                // Revert quantity if update failed on server
                quantityInput.value = currentQuantity;
                subtotalSpan.textContent = (currentQuantity * itemPrice).toFixed(2);
                showMessage(data.message || 'Failed to update quantity.', 'error');
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
            // Revert quantity on network error
            quantityInput.value = currentQuantity;
            subtotalSpan.textContent = (currentQuantity * itemPrice).toFixed(2);
            showMessage('Network error. Could not update quantity.', 'error');
        }
    }

    // Function to recalculate and update overall cart totals
    function updateCartTotals() {
        let newTotal = 0;
        document.querySelectorAll('#cart-items-container .cart-item-card').forEach(card => {
            const quantity = parseInt(card.querySelector('.quantity-input').value);
            const price = parseFloat(card.dataset.itemPrice);
            newTotal += quantity * price;
        });

        document.getElementById('cart-subtotal').textContent = `DA${newTotal.toFixed(2)}`;
        document.getElementById('cart-grand-total').textContent = `DA${newTotal.toFixed(2)}`;
    }

    // Function to remove an item from the cart (called by custom confirm)
    async function removeItemConfirmed(itemId) {
        try {
            const response = await fetch('update_cart_quantity.php', { // Re-use update endpoint with quantity 0
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}&quantity=0` // Set quantity to 0 to remove
            });

            const data = await response.json();

            if (data.success) {
                const itemCard = document.querySelector(`.cart-item-card[data-item-id="${itemId}"]`);
                if (itemCard) {
                    itemCard.remove();
                    updateCartTotals();
                    showMessage('Item removed from cart.', 'success');
                    if (document.querySelectorAll('#cart-items-container .cart-item-card').length === 0) {
                        window.location.reload(); // Reload to show empty cart state
                    }
                }
            } else {
                showMessage(data.message || 'Failed to remove item.', 'error');
            }
        } catch (error) {
            console.error('Error removing item:', error);
            showMessage('Network error. Could not remove item.', 'error');
        }
    }

    // Function to move an item to likes (wishlist)
    async function moveToLikes(itemId) {
        // First, remove from cart
        try {
            const responseRemove = await fetch('update_cart_quantity.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `item_id=${itemId}&quantity=0`
            });
            const dataRemove = await responseRemove.json();

            if (dataRemove.success) {
                const itemCard = document.querySelector(`.cart-item-card[data-item-id="${itemId}"]`);
                if (itemCard) {
                    itemCard.remove();
                    updateCartTotals();
                    showMessage('Item moved to Likes and removed from cart.', 'success');

                    // Now, add to wishlist (assuming add_to_wishlist.php exists and works)
                    try {
                        const responseAddWishlist = await fetch('add_to_wishlist.php', { // You might need to create this file
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `product_id=${itemId}`
                        });
                        const dataAddWishlist = await responseAddWishlist.json();
                        if (!dataAddWishlist.success) {
                            console.warn('Failed to add item to wishlist:', dataAddWishlist.message);
                            // Optionally show a less intrusive message or log for user
                        }
                    } catch (wishlistError) {
                        console.error('Error adding to wishlist:', wishlistError);
                    }

                    if (document.querySelectorAll('#cart-items-container .cart-item-card').length === 0) {
                        window.location.reload();
                    }
                }
            } else {
                showMessage(dataRemove.message || 'Failed to move item to Likes.', 'error');
            }
        } catch (error) {
            console.error('Error moving item:', error);
            showMessage('Network error. Could not move item to Likes.', 'error');
        }
    }

    // Function to clear the entire cart (called by custom confirm)
    async function clearCartConfirmed() {
        try {
            const response = await fetch('clear_cart.php', { // NEW PHP FILE
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=<?= $_SESSION['id'] ?>` // Pass user ID
            });

            const data = await response.json();

            if (data.success) {
                showMessage('Your cart has been cleared!', 'success');
                setTimeout(() => {
                    window.location.reload(); // Reload to show empty cart state
                }, 1000);
            } else {
                showMessage(data.message || 'Failed to clear cart.', 'error');
            }
        } catch (error) {
            console.error('Error clearing cart:', error);
            showMessage('Network error. Could not clear cart.', 'error');
        }
    }

    // Event delegation for quantity buttons and remove/move to likes buttons
    document.addEventListener('click', (event) => {
        const target = event.target;

        // Handle quantity buttons
        if (target.classList.contains('quantity-btn')) {
            const itemCard = target.closest('.cart-item-card');
            if (!itemCard) return;
            const itemId = parseInt(itemCard.dataset.itemId);
            const change = parseInt(target.dataset.change);
            updateQuantity(itemId, change);
        }

        // Handle remove item button
        if (target.classList.contains('remove-item-btn')) {
            const itemCard = target.closest('.cart-item-card');
            if (!itemCard) return;
            const itemId = parseInt(itemCard.dataset.itemId);
            showCustomConfirm('Are you sure you want to remove this item from your cart?', () => removeItemConfirmed(itemId));
        }

        // Handle move to likes button
        if (target.classList.contains('move-to-likes-btn')) {
            const itemCard = target.closest('.cart-item-card');
            if (!itemCard) return;
            const itemId = parseInt(itemCard.dataset.itemId);
            moveToLikes(itemId);
        }
    });

    // Handle quantity input change
    document.addEventListener('change', (event) => {
        const target = event.target;
        if (target.classList.contains('quantity-input')) {
            const itemCard = target.closest('.cart-item-card');
            if (!itemCard) return;
            const itemId = parseInt(itemCard.dataset.itemId);
            const newQuantity = parseInt(target.value);
            updateQuantity(itemId, 0, newQuantity); // Pass 0 for change, and the direct value
        }
    });


    // Initial calculation on page load
    document.addEventListener('DOMContentLoaded', updateCartTotals);
</script>
</body>
</html>
