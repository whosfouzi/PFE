<?php
session_start();

// Database connection
$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    // Log database connection error
    error_log("Database Connection failed: " . $db->connect_error);
    // In a production environment, you might want a more robust error page or message
    die("Database connection failed. Please try again later.");
}
$db->set_charset("utf8mb4"); // Ensure correct character set

$user_id = $_SESSION["id"] ?? null;

// --- Handle POST Requests (Add, Update, Remove) ---
// This block processes actions like adding a product from products.php,
// updating quantity, or removing an item from the cart page itself.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Handle Add to Cart (from products.php or product detail page) ---
    if (isset($_POST["add_to_cart"])) {
        // Check if user is logged in before adding to cart
        if (!$user_id) {
            // Store the intended destination so user can be redirected back after login
            $_SESSION['redirect_after_login'] = 'shopping_cart.php'; // Redirect to cart after login
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Please log in to add items to your cart.'];
            header("Location: login.php");
            exit();
        }

        // Get product ID and quantity from the POST data
        // FIX: Correctly getting product_id and item_quantity from $_POST
        $productId = $_POST["product_id"] ?? null;
        $quantity = $_POST["item_quantity"] ?? 1; // Default quantity to 1 if not provided

        // Validate product ID and quantity
        if (!filter_var($productId, FILTER_VALIDATE_INT) || $productId <= 0 || !filter_var($quantity, FILTER_VALIDATE_INT) || $quantity <= 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid product or quantity provided.'];
            error_log("Add to Cart Error: Invalid product ID ($productId) or quantity ($quantity) for user ID: " . ($user_id ?? 'NULL'));
            header("Location: shopping_cart.php"); // Redirect to cart page
            exit();
        }

        // Check product stock before adding
        $stock_check_stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
        if (!$stock_check_stmt) {
             error_log("Add to Cart Error: Stock check prepare failed: " . $db->error);
             $_SESSION['message'] = ['type' => 'error', 'text' => 'Error checking product stock.'];
             header("Location: shopping_cart.php");
             exit();
        }
        $stock_check_stmt->bind_param("i", $productId);
        $stock_check_stmt->execute();
        $stock_check_stmt->bind_result($availableStock);
        $stock_check_stmt->fetch();
        $stock_check_stmt->close();

        if ($quantity > $availableStock) {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Not enough stock for this item. Only $availableStock available."];
            error_log("Add to Cart Error: Quantity requested ($quantity) exceeds stock ($availableStock) for product ID: $productId, user ID: $user_id");
            header("Location: shopping_cart.php");
            exit();
        }

        // Find or create a cart for the logged-in user
        $cart_id = null;
        $stmt_cart = $db->prepare("SELECT id FROM cart WHERE user_id = ?");
         if (!$stmt_cart) {
             error_log("Add to Cart Error: Cart check prepare failed: " . $db->error);
             $_SESSION['message'] = ['type' => 'error', 'text' => 'Error checking user cart.'];
             header("Location: shopping_cart.php");
             exit();
        }
        $stmt_cart->bind_param("i", $user_id);
        $stmt_cart->execute();
        $stmt_cart->bind_result($cart_id);
        $stmt_cart->fetch();
        $stmt_cart->close();

        if (!$cart_id) {
            // Cart doesn't exist for this user, create a new one
            $stmt_insert_cart = $db->prepare("INSERT INTO cart (user_id) VALUES (?)");
             if (!$stmt_insert_cart) {
                 error_log("Add to Cart Error: Insert cart prepare failed: " . $db->error);
                 $_SESSION['message'] = ['type' => 'error', 'text' => 'Error creating user cart.'];
                 header("Location: shopping_cart.php");
                 exit();
            }
            $stmt_insert_cart->bind_param("i", $user_id);
            if ($stmt_insert_cart->execute()) {
                 $cart_id = $stmt_insert_cart->insert_id; // Get the ID of the newly created cart
                 error_log("Add to Cart Info: New cart created with ID: $cart_id for user ID: $user_id");
            } else {
                 error_log("Add to Cart Error: Failed to execute insert cart: " . $stmt_insert_cart->error);
                 $_SESSION['message'] = ['type' => 'error', 'text' => 'Error creating user cart.'];
                 header("Location: shopping_cart.php");
                 exit();
            }
            $stmt_insert_cart->close();
        }

        // Check if the product is already in the user's cart
        $existing_item_quantity = 0;
        $stmt_check_item = $db->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
         if (!$stmt_check_item) {
             error_log("Add to Cart Error: Check cart item prepare failed: " . $db->error);
             $_SESSION['message'] = ['type' => 'error', 'text' => 'Error checking cart item.'];
             header("Location: shopping_cart.php");
             exit();
        }
        $stmt_check_item->bind_param("ii", $cart_id, $productId);
        $stmt_check_item->execute();
        $stmt_check_item->bind_result($existing_item_quantity);
        $stmt_check_item->fetch();
        $stmt_check_item->close();

        if ($existing_item_quantity > 0) {
            // Item exists, update quantity
            $new_quantity = $existing_item_quantity + $quantity;
            // Re-check stock against the *new* total quantity
             if ($new_quantity > $availableStock) {
                $_SESSION['message'] = ['type' => 'error', 'text' => "Adding more would exceed stock. Only $availableStock available in total."];
                 error_log("Add to Cart Error: Cumulative quantity ($new_quantity) exceeds stock ($availableStock) for product ID: $productId, user ID: $user_id");
            } else {
                $stmt_update_item = $db->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
                 if (!$stmt_update_item) {
                     error_log("Add to Cart Error: Update cart item prepare failed: " . $db->error);
                     $_SESSION['message'] = ['type' => 'error', 'text' => 'Error updating cart item quantity.'];
                     header("Location: shopping_cart.php");
                     exit();
                }
                $stmt_update_item->bind_param("iii", $new_quantity, $cart_id, $productId);
                if ($stmt_update_item->execute()) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Product quantity updated in cart!'];
                    error_log("Add to Cart Info: Updated quantity to $new_quantity for product ID: $productId, user ID: $user_id");
                } else {
                    error_log("Add to Cart Error: Failed to execute update cart item: " . $stmt_update_item->error);
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Error updating cart item quantity.'];
                }
                $stmt_update_item->close();
            }
        } else {
            // Item does not exist, insert new item into cart_items
            $stmt_insert_item = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
             if (!$stmt_insert_item) {
                 error_log("Add to Cart Error: Insert cart item prepare failed: " . $db->error);
                 $_SESSION['message'] = ['type' => 'error', 'text' => 'Error adding product to cart.'];
                 header("Location: shopping_cart.php");
                 exit();
            }
            $stmt_insert_item->bind_param("iii", $cart_id, $productId, $quantity);
            if ($stmt_insert_item->execute()) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Product added to cart!'];
                 error_log("Add to Cart Info: Inserted product ID: $productId with quantity $quantity for user ID: $user_id");
            } else {
                error_log("Add to Cart Error: Failed to execute insert cart item: " . $stmt_insert_item->error);
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Error adding product to cart.'];
            }
            $stmt_insert_item->close();
        }

    } elseif (isset($_POST["update_quantity"]) && $user_id) {
        // --- Handle Update Quantity (from the cart page) ---
        $itemId = $_POST["item_id"] ?? null;
        $quantity = $_POST["item_quantity"] ?? null;

        // Validate item ID and quantity
        if (!filter_var($itemId, FILTER_VALIDATE_INT) || $itemId <= 0 || $quantity === null || !filter_var($quantity, FILTER_VALIDATE_INT) || $quantity < 0) { // Allow 0 for removal via update
             $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid item or quantity for update.'];
             error_log("Update Quantity Error: Invalid item ID ($itemId) or quantity ($quantity) for user ID: " . ($user_id ?? 'NULL'));
             header("Location: shopping_cart.php");
             exit();
        }

        if ($quantity == 0) {
             // If quantity is 0, treat as remove
             $delete_stmt = $db->prepare("DELETE ci FROM cart_items ci JOIN cart c ON ci.cart_id = c.id WHERE ci.product_id = ? AND c.user_id = ?");
              if (!$delete_stmt) {
                 error_log("Update Quantity Error: Delete on update prepare failed: " . $db->error);
                 $_SESSION['message'] = ['type' => 'error', 'text' => 'Error preparing to remove item.'];
                 header("Location: shopping_cart.php");
                 exit();
            }
             $delete_stmt->bind_param("ii", $itemId, $user_id);
             if ($delete_stmt->execute()) {
                 $_SESSION['message'] = ['type' => 'success', 'text' => 'Product removed from cart.'];
                  error_log("Update Quantity Info: Removed product ID: $itemId via quantity 0 for user ID: $user_id");
             } else {
                 error_log("Update Quantity Error: Failed to execute delete on update: " . $delete_stmt->error);
                 $_SESSION['message'] = ['type' => 'error', 'text' => 'Error removing item.'];
             }
             $delete_stmt->close();

        } else {
             // Check product stock before updating quantity
            $stock_check_stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
             if (!$stock_check_stmt) {
                 error_log("Update Quantity Error: Stock check prepare failed: " . $db->error);
                 $_SESSION['message'] = ['type' => 'error', 'text' => 'Error checking product stock for update.'];
                 header("Location: shopping_cart.php");
                 exit();
            }
            $stock_check_stmt->bind_param("i", $itemId);
            $stock_check_stmt->execute();
            $stock_check_stmt->bind_result($availableStock);
            $stock_check_stmt->fetch();
            $stock_check_stmt->close();

            if ($quantity > $availableStock) {
                $_SESSION['message'] = ['type' => 'error', 'text' => "Not enough stock for this item. Only $availableStock available."];
                 error_log("Update Quantity Error: Quantity requested ($quantity) exceeds stock ($availableStock) for product ID: $itemId, user ID: $user_id");
            } else {
                // Update quantity in cart_items table
                $update_stmt = $db->prepare("UPDATE cart_items ci JOIN cart c ON ci.cart_id = c.id SET quantity = ? WHERE ci.product_id = ? AND c.user_id = ?");
                 if (!$update_stmt) {
                     error_log("Update Quantity Error: Update cart item prepare failed: " . $db->error);
                     $_SESSION['message'] = ['type' => 'error', 'text' => 'Error updating cart item quantity.'];
                     header("Location: shopping_cart.php");
                     exit();
                }
                $update_stmt->bind_param("iii", $quantity, $itemId, $user_id);
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Cart item quantity updated.'];
                     error_log("Update Quantity Info: Updated quantity to $quantity for product ID: $itemId, user ID: $user_id");
                } else {
                     error_log("Update Quantity Error: Failed to execute update cart item: " . $update_stmt->error);
                     $_SESSION['message'] = ['type' => 'error', 'text' => 'Error updating cart item quantity.'];
                }
                $update_stmt->close();
            }
        }

    } elseif (isset($_POST["remove_item"]) && $user_id) {
        // --- Handle Remove Item (from the cart page) ---
        $itemId = $_POST["item_id"] ?? null;
         if (!filter_var($itemId, FILTER_VALIDATE_INT) || $itemId <= 0) {
             $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid item for removal.'];
             error_log("Remove Item Error: Invalid item ID ($itemId) for user ID: " . ($user_id ?? 'NULL'));
             header("Location: shopping_cart.php");
             exit();
         }
        // Delete the item from cart_items table
        $delete_stmt = $db->prepare("DELETE ci FROM cart_items ci JOIN cart c ON ci.cart_id = c.id WHERE ci.product_id = ? AND c.user_id = ?");
         if (!$delete_stmt) {
             error_log("Remove Item Error: Delete prepare failed: " . $db->error);
             $_SESSION['message'] = ['type' => 'error', 'text' => 'Error preparing to remove item.'];
             header("Location: shopping_cart.php");
             exit();
         }
        $delete_stmt->bind_param("ii", $itemId, $user_id);
        if ($delete_stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Product removed from cart.'];
             error_log("Remove Item Info: Removed product ID: $itemId for user ID: $user_id");
        } else {
            error_log("Remove Item Error: Failed to execute delete: " . $delete_stmt->error);
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error removing product from cart.'];
        }
        $delete_stmt->close();
    }

    // Redirect after any POST request to prevent form resubmission on refresh
    // This also ensures the page reloads to show the updated cart content
    header("Location: shopping_cart.php");
    exit();
}

// --- Handle GET Requests (Initial page load, or redirects) ---
// This block runs when the page is loaded via a GET request (initial load or after a redirect)
$shopping_cart = []; // Initialize an empty array
$total = 0;

if ($user_id) {
    // Fetch cart items from the database for the logged-in user
    $stmt = $db->prepare("
        SELECT
            ci.product_id AS item_id,
            p.name AS item_name,
            p.price AS item_price,
            ci.quantity AS item_quantity,
            p.image AS item_image,
            p.stock AS item_stock -- Fetch stock to display in cart
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.id
        JOIN products p ON ci.product_id = p.id
        WHERE c.user_id = ?
    ");
     if (!$stmt) {
         error_log("Fetch Cart Error: Fetch cart prepare failed: " . $db->error);
         // Fallback to empty cart if fetch fails
         // $shopping_cart will remain empty
     } else {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $shopping_cart = $result->fetch_all(MYSQLI_ASSOC); // Store fetched data in $shopping_cart
        // We don't strictly need to store the full cart in $_SESSION here for display,
        // but it might be used elsewhere. Let's keep it updated.
        $_SESSION["shopping_cart"] = $shopping_cart;
        $stmt->close();
     }
} else {
    // If user is not logged in, the cart is empty.
    // $shopping_cart should already be empty from initialization.
    // Optional: Add a message if they were redirected here without logging in
    // if (!isset($_SESSION['message'])) {
    //      $_SESSION['message'] = ['type' => 'info', 'text' => 'Log in to see and manage your cart.'];
    // }
}

// Calculate total based on the fetched $shopping_cart data
foreach ($shopping_cart as $item) {
    // Ensure item_price and item_quantity are numeric before calculation to prevent errors
    $price = is_numeric($item["item_price"]) ? $item["item_price"] : 0;
    $quantity = is_numeric($item["item_quantity"]) ? $item["item_quantity"] : 0;
    $total += $price * $quantity;
}

$db->close(); // Close database connection after fetching data
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GiftStore - Shopping Cart</title>
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
        .focus\:ring-turquoise-primary:focus {
            --tw-ring-color: #56c8d8;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">

    <?php include("navbar.php"); // Include your navigation bar ?>

    <div class="max-w-6xl mx-auto px-4 py-12 min-h-screen bg-gray-50 rounded-lg shadow-sm my-8">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-10 text-center">Your Shopping Cart</h1>

        <?php
        // Display session messages (success or error alerts)
        if (isset($_SESSION['message'])): ?>
            <div id="alertMessage" class="mb-6 p-4 rounded-lg shadow-md text-white
                <?= $_SESSION['message']['type'] == 'success' ? 'bg-green-500' : 'bg-red-500' ?>">
                <?= htmlspecialchars($_SESSION['message']['text']) ?>
            </div>
            <script>
                // Use DOMContentLoaded to ensure the element exists before trying to remove it
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(() => {
                        const alertElement = document.getElementById('alertMessage');
                        if (alertElement) {
                            alertElement.remove();
                        }
                    }, 5000); // Message disappears after 5 seconds
                });
            </script>
            <?php unset($_SESSION['message']); // Clear the message after displaying ?>
        <?php endif; ?>


        <?php if (!empty($shopping_cart)): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Items in Cart</h2>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($shopping_cart as $item): ?>
                            <div class="flex flex-col md:flex-row items-center py-5">
                                <img src="uploads/<?= htmlspecialchars($item['item_image'] ?? 'no-image.jpg') ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" class="w-24 h-24 object-cover rounded-lg mr-6 mb-4 md:mb-0 shadow-sm">
                                <div class="flex-grow text-center md:text-left">
                                    <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($item['item_name']) ?></h3>
                                    <p class="text-md text-turquoise-primary font-bold mt-1">DA <?= number_format($item['item_price'], 2) ?></p>
                                    <p class="text-sm text-gray-500">In Stock: <?= htmlspecialchars($item['item_stock']) ?></p>
                                    <?php if ($item['item_quantity'] > $item['item_stock']): ?>
                                        <p class="text-red-500 text-sm font-medium mt-1">Quantity exceeds available stock! Max: <?= htmlspecialchars($item['item_stock']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center mt-4 md:mt-0">
                                    <form method="POST" class="flex items-center">
                                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                        <input type="number" name="item_quantity" value="<?= htmlspecialchars($item['item_quantity']) ?>" min="0" max="<?= htmlspecialchars($item['item_stock']) ?>"
                                               class="w-20 px-3 py-2 border border-gray-300 rounded-md text-center text-gray-800 focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary">
                                        <button type="submit" name="update_quantity" class="ml-3 px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                                            Update
                                        </button>
                                    </form>
                                    <form method="POST" class="ml-4">
                                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                        <button type="submit" name="remove_item" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors flex items-center">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit sticky top-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Order Summary</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between text-lg font-medium text-gray-700">
                            <span>Subtotal:</span>
                            <span class="text-turquoise-primary">DA <?= number_format($total, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-xl font-bold text-gray-900 pt-4 border-t border-gray-200">
                            <span>Total:</span>
                            <span class="text-turquoise-primary">DA <?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                    <div class="mt-8">
                        <a href="checkout.php" class="block w-full text-center bg-turquoise-primary hover:bg-cyan-700 text-white px-6 py-3 rounded-lg text-lg font-semibold transition-colors shadow-lg">
                            Proceed to Checkout
                        </a>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="index.php" class="inline-block text-gray-600 hover:text-turquoise-primary transition-colors">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center p-12 bg-white rounded-lg shadow-md text-center">
                <img src="https://sugarwish.com/images/empty-cart-bg.png" alt="Empty Cart" class="w-64 h-auto mb-8 opacity-80">
                <p class="text-2xl font-semibold text-gray-700 mb-4">
                    Your cart is empty!
                </p>
                <p class="text-gray-500 text-md mb-8">
                    Looks like you haven't added any gifts yet.
                </p>
                <a href="index.php" class="inline-block bg-turquoise-primary hover:bg-cyan-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition-colors shadow-md">
                    Start Shopping Now
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include("footer.php"); // Include your footer ?>

</body>
</html>
