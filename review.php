<?php
session_start();

// Database connection
$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    error_log("Database Connection failed: " . $db->connect_error);
    die("Database connection failed. Please try again later.");
}
$db->set_charset("utf8mb4");

$user_id = $_SESSION["id"] ?? null;

// Redirect if user is not logged in
if (!$user_id) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']; // Store current page for redirection
    header("Location: login.php");
    exit();
}

$order_id = $_GET['order_id'] ?? null;
$order_details = null;
$order_items = [];
$existing_review = null;
$error_message = '';
$success_message = '';

// --- Handle Review Submission (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $submitted_order_id = $_POST['order_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $comment = trim($_POST['comment'] ?? '');

    // Validate submitted data
    if (!filter_var($submitted_order_id, FILTER_VALIDATE_INT) || $submitted_order_id <= 0 ||
        !filter_var($rating, FILTER_VALIDATE_INT) || $rating < 1 || $rating > 5) {
        $error_message = "Invalid review data submitted (Order ID or Rating).";
        error_log("Review Submission Error: Invalid data for user ID: $user_id, Order ID: $submitted_order_id, Rating: $rating");
    } elseif (empty($comment)) { // NEW: Check if comment is empty
        $error_message = "Review comment cannot be empty.";
        error_log("Review Submission Error: Empty comment for user ID: $user_id, Order ID: $submitted_order_id");
    } else {
        // Verify the order belongs to the user and is completed
        $stmt_verify_order = $db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND order_status = 'completed'");
        if (!$stmt_verify_order) {
            error_log("Review Submission Error: Verify order prepare failed: " . $db->error);
            $error_message = "An error occurred verifying your order.";
        } else {
            $stmt_verify_order->bind_param("ii", $submitted_order_id, $user_id);
            $stmt_verify_order->execute();
            $stmt_verify_order->store_result();

            if ($stmt_verify_order->num_rows === 0) {
                $error_message = "Order not found, not completed, or does not belong to you.";
                error_log("Review Submission Error: Order verification failed for user ID: $user_id, Order ID: $submitted_order_id");
            } else {
                // Check if a review already exists for this order by this user
                $stmt_check_review = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND order_id = ?");
                if (!$stmt_check_review) {
                    error_log("Review Submission Error: Check review prepare failed: " . $db->error);
                    $error_message = "An error occurred checking for existing review.";
                } else {
                    $stmt_check_review->bind_param("ii", $user_id, $submitted_order_id);
                    $stmt_check_review->execute();
                    $stmt_check_review->store_result();

                    if ($stmt_check_review->num_rows > 0) {
                        $error_message = "You have already submitted a review for this order.";
                        error_log("Review Submission Error: Duplicate review attempt for user ID: $user_id, Order ID: $submitted_order_id");
                    } else {
                        // Insert the new review
                        $stmt_insert_review = $db->prepare("INSERT INTO reviews (user_id, order_id, rating, comment) VALUES (?, ?, ?, ?)");
                        if (!$stmt_insert_review) {
                            error_log("Review Submission Error: Insert review prepare failed: " . $db->error);
                            $error_message = "An error occurred saving your review.";
                        } else {
                            // Comment is now required, so no need to check if empty for binding null
                            $stmt_insert_review->bind_param("iiis", $user_id, $submitted_order_id, $rating, $comment);

                            if ($stmt_insert_review->execute()) {
                                $success_message = "Your review has been submitted successfully!";
                                error_log("Review Submission Info: Review submitted for user ID: $user_id, Order ID: $submitted_order_id");
                            } else {
                                $error_message = "Failed to save your review. Please try again.";
                                error_log("Review Submission Error: Failed to execute insert review: " . $stmt_insert_review->error);
                            }
                            $stmt_insert_review->close();
                        }
                    }
                    $stmt_check_review->close();
                }
            }
            $stmt_verify_order->close();
        }
    }

    // Redirect after POST to prevent form resubmission and show message
    $redirect_url = 'my_account.php#orders'; // Redirect back to orders section
    if ($success_message) {
        $redirect_url .= '&review_message=' . urlencode($success_message);
    } elseif ($error_message) {
        $redirect_url .= '&review_error=' . urlencode($error_message);
    }
    header("Location: " . $redirect_url);
    exit();
}

// --- Fetch Order Details and Check for Existing Review (GET request) ---
// This runs when the page is initially loaded
if ($order_id) {
    // Fetch order details for the logged-in user
    $stmt_order = $db->prepare("SELECT id, created_at, total_price, order_status FROM orders WHERE id = ? AND user_id = ?");
    if (!$stmt_order) {
        error_log("Review Page Error: Fetch order prepare failed: " . $db->error);
        $error_message = "An error occurred fetching order details.";
    } else {
        $stmt_order->bind_param("ii", $order_id, $user_id);
        $stmt_order->execute();
        $result_order = $stmt_order->get_result();
        if ($result_order->num_rows > 0) {
            $order_details = $result_order->fetch_assoc();

            // Check if the order is completed
            if (strtolower($order_details['order_status']) !== 'completed') {
                $error_message = "This order is not yet completed and cannot be reviewed.";
                $order_details = null; // Clear details if not completed
                error_log("Review Page Error: Attempted to review non-completed order ID: $order_id for user ID: $user_id");
            } else {
                // Fetch order items
                $stmt_items = $db->prepare("SELECT product_name, quantity, unit_price FROM order_items WHERE order_id = ?");
                if (!$stmt_items) {
                    error_log("Review Page Error: Fetch order items prepare failed: " . $db->error);
                    $error_message = "An error occurred fetching order items."; // Keep order details, but show item error
                } else {
                    $stmt_items->bind_param("i", $order_id);
                    $stmt_items->execute();
                    $order_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt_items->close();
                }

                // Check if a review already exists for this order by this user
                $stmt_existing_review = $db->prepare("SELECT rating, comment, created_at FROM reviews WHERE user_id = ? AND order_id = ?");
                if (!$stmt_existing_review) {
                    error_log("Review Page Error: Check existing review prepare failed: " . $db->error);
                    // Continue without showing existing review if error
                } else {
                    $stmt_existing_review->bind_param("ii", $user_id, $order_id);
                    $stmt_existing_review->execute();
                    $result_existing_review = $stmt_existing_review->get_result();
                    if ($result_existing_review->num_rows > 0) {
                        $existing_review = $result_existing_review->fetch_assoc();
                    }
                    $stmt_existing_review->close();
                }
            }

        } else {
            $error_message = "Order not found or does not belong to you.";
            error_log("Review Page Error: Order ID ($order_id) not found or user mismatch for user ID: " . ($user_id ?? 'NULL'));
        }
        $stmt_order->close();
    }
} else {
    $error_message = "No order specified for review.";
    error_log("Review Page Error: No order_id provided for user ID: " . ($user_id ?? 'NULL'));
}


$db->close(); // Close database connection

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Order - GiftStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6; /* Tailwind bg-gray-100 */
        }
         /* Custom styles for turquoise */
        .text-turquoise-primary { color: #56c8d8; }
        .bg-turquoise-primary { background-color: #56c8d8; }
        .border-turquoise-primary { border-color: #56c8d8; }
        .focus\:ring-turquoise-primary:focus { --tw-ring-color: #56c8d8; }
        .hover\:bg-cyan-700:hover { background-color: #45b1c0; } /* A darker turquoise */

        /* Star Rating Styles */
        .rating {
            display: inline-block;
            unicode-bidi: bidi-override;
            direction: rtl;
        }
        .rating > input {
            display: none;
        }
        .rating > label {
            float: right;
            display: inline-block;
            padding: 0 5px; /* Adjust spacing between stars */
            cursor: pointer;
            font-size: 2rem; /* Adjust star size */
            color: #ccc; /* Default star color */
            transition: color 0.2s ease-in-out;
        }
        .rating > input:checked ~ label,
        .rating > label:hover,
        .rating > label:hover ~ label {
            color: #ffc107; /* Gold color for selected/hovered stars */
        }
         /* Ensure half stars work if needed, though this simple setup is for full stars */
        .rating > input:checked + label:hover,
        .rating > input:checked ~ label:hover:not(.active),
        .rating > label:hover:not(.active) ~ input:checked ~ label {
             color: #ffc107;
        }
         /* Style for displaying static stars */
         .static-rating {
            display: inline-block;
            font-size: 1.5rem; /* Adjust size for display */
            color: #ffc107; /* Gold color */
            letter-spacing: 2px; /* Space out stars */
         }
         .static-rating .empty-star {
             color: #ccc; /* Grey for empty stars */
         }
    </style>
</head>
<body class="bg-gray-100 antialiased">

    <?php include("navbar.php"); ?>

    <div class="max-w-3xl mx-auto px-4 py-12">

        <?php if ($error_message): ?>
            <div class="mb-6 p-4 rounded-lg shadow-md bg-red-500 text-white">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php elseif ($success_message): ?>
             <div class="mb-6 p-4 rounded-lg shadow-md bg-green-500 text-white">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($order_details && empty($error_message)): // Only show review form if order details are valid and no errors ?>

            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Review Your Order #<?= htmlspecialchars($order_details['id']) ?></h1>
                <p class="text-gray-600 text-sm mb-6">Ordered on <?= date('F j, Y', strtotime($order_details['created_at'])) ?></p>

                <h3 class="text-lg font-semibold text-gray-700 mb-3">Items in this Order:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-600 mb-6">
                    <?php if (!empty($order_items)): ?>
                        <?php foreach ($order_items as $item): ?>
                            <li><?= htmlspecialchars($item['quantity']) ?> x <?= htmlspecialchars($item['product_name']) ?> (DA <?= number_format($item['unit_price'], 2) ?> each)</li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Could not load order items.</li>
                    <?php endif; ?>
                </ul>

                <?php if ($existing_review): ?>
                    <div class="mt-8 p-4 bg-gray-100 rounded-md">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Your Submitted Review:</h3>
                        <div class="static-rating mb-2">
                            <?php
                            $filled_stars = $existing_review['rating'];
                            $empty_stars = 5 - $filled_stars;
                            echo str_repeat('★', $filled_stars);
                            echo str_repeat('<span class="empty-star">★</span>', $empty_stars);
                            ?>
                        </div>
                        <?php if (!empty($existing_review['comment'])): // Comment is now required, but display it if it exists from older reviews ?>
                             <p class="text-gray-700 italic">"<?= nl2br(htmlspecialchars($existing_review['comment'])) ?>"</p>
                        <?php endif; ?>
                         <p class="text-sm text-gray-500 mt-2">Submitted on <?= date('F j, Y', strtotime($existing_review['created_at'])) ?></p>
                    </div>
                     <div class="mt-6 text-center">
                         <a href="my_account.php#orders" class="inline-block bg-turquoise-primary hover:bg-cyan-700 text-white px-6 py-3 rounded-lg text-lg font-semibold transition-colors shadow-md">
                            Back to My Orders
                        </a>
                     </div>
                <?php else: ?>
                    <form method="POST" action="review.php" class="space-y-6">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_details['id']) ?>">

                        <div>
                            <label class="block text-lg font-semibold text-gray-700 mb-2">Your Rating:</label>
                            <div class="rating">
                                <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="5 stars">★</label>
                                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars">★</label>
                                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars">★</label>
                                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars">★</label>
                                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star">★</label>
                            </div>
                        </div>

                        <div>
                            <label for="comment" class="block text-lg font-semibold text-gray-700 mb-2">Your Comment:</label>
                            <textarea id="comment" name="comment" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary" placeholder="Share your experience..."></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="submit_review" class="bg-turquoise-primary hover:bg-cyan-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition-colors shadow-md">
                                Submit Review
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            </div>

        <?php elseif (empty($error_message)): ?>
             <div class="bg-white p-6 rounded-lg shadow-md text-center">
                 <p class="text-xl font-semibold text-gray-700">Please select a completed order from your account to leave a review.</p>
                 <div class="mt-6">
                    <a href="my_account.php#orders" class="inline-block bg-turquoise-primary hover:bg-cyan-700 text-white px-6 py-3 rounded-lg text-lg font-semibold transition-colors shadow-md">
                        Go to My Orders
                    </a>
                 </div>
             </div>
        <?php endif; ?>

    </div>

    <?php include("footer.php"); ?>

</body>
</html>