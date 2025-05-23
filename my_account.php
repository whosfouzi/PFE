<?php
session_start();

if (!isset($_SESSION['id'])) {
    // Store the current URL to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$db = new mysqli("localhost", "root", "", "giftstore");

if ($db->connect_error) {
    // Log database connection error
    error_log("Database Connection failed: " . $db->connect_error);
    // In a production environment, you might want a more robust error page or message
    die("Database connection failed. Please try again later.");
}

$user_id = $_SESSION['id'];

// Fetch orders
// Added order_status to the SELECT statement if it wasn't already there
$order_stmt = $db->prepare("
    SELECT
        o.id,
        o.created_at,
        o.order_status,
        o.total_price,
        GROUP_CONCAT(oi.product_name SEPARATOR ', ') AS product_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

if (!$order_stmt) {
    error_log("My Account Orders Prepare failed: " . $db->error);
    $orders = []; // Initialize as empty array on error
} else {
    $order_stmt->bind_param("i", $user_id);
    $order_stmt->execute();
    $orders = $order_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $order_stmt->close();
}


// Fetch wishlist items (now "Likes")
$likes_stmt = $db->prepare("
    SELECT p.id, p.name, p.price, p.image
    FROM wishlist w /* Assuming 'wishlist' is still the table name */
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
");
if (!$likes_stmt) {
    error_log("My Account Likes Prepare failed: " . $db->error);
    $likes = []; // Initialize as empty array on error
} else {
    $likes_stmt->bind_param("i", $user_id);
    $likes_stmt->execute();
    $likes = $likes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $likes_stmt->close();
}


// Fetch user info
$stmt = $db->prepare("SELECT username, email, fname, lname, phone, created_at FROM users WHERE id = ?");
if (!$stmt) {
    error_log("My Account User Info Prepare failed: " . $db->error);
    $user = []; // Initialize as empty array on error
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}


$db->close(); // Close database connection
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Account - <?= htmlspecialchars($user['username'] ?? 'User') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6; /* Tailwind bg-gray-100 */
        }

        /* Responsive grid layout: sidebar fixed width on large screens, full width on smaller */
        .account-grid {
            display: grid;
            grid-template-columns: 1fr; /* Single column for mobile */
            gap: 1.5rem; /* 24px */
        }

        @media (min-width: 1024px) { /* lg breakpoint */
            .account-grid {
                grid-template-columns: 320px 1fr; /* Fixed width sidebar, flexible main content */
            }
        }

        .profile-initial-circle {
            width: 80px; /* Same as screenshot "Aa" circle */
            height: 80px;
            border-radius: 50%;
            background-color: #9ca3af; /* Tailwind gray-400 */
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem; /* 32px */
            font-weight: 600; /* semibold */
        }

        /* Pill Navigation Styles */
        .pill-nav-container {
            background-color: #d1d5db; /* Tailwind gray-300 */
            border-radius: 9999px; /* full */
            padding: 0.25rem; /* p-1 */
            display: flex;
            justify-content: space-around; /* Distribute items evenly */
            margin-bottom: 1.5rem; /* mb-6 */
             /* Allow wrapping on smaller screens */
            flex-wrap: wrap;
            gap: 0.5rem; /* Add gap for wrapped items */
        }

        .pill-nav-link {
            padding: 0.5rem 1.25rem; /* py-2 px-5, adjust for desired width */
            border-radius: 9999px; /* full */
            text-decoration: none;
            color: #374151; /* Tailwind text-gray-700 */
            font-weight: 500; /* medium */
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            text-align: center;
            flex-grow: 1; /* Make items take available space if needed */
            min-width: fit-content; /* Prevent shrinking too much */
             cursor: pointer; /* Indicate it's clickable */
        }

        .pill-nav-link:hover {
            color: #111827; /* Tailwind text-gray-900 */
            background-color: #e5e7eb; /* Tailwind gray-200 */
        }

        .pill-nav-link.active {
            background-color: #ffffff; /* white */
            color: #56c8d8; /* Your primary turquoise color */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* shadow-md */
        }

        /* Styling for content sections */
        .content-section-card {
            background-color: #ffffff; /* white */
            border-radius: 0.75rem; /* rounded-xl */
            padding: 1.5rem; /* p-6 */
            margin-bottom: 1.5rem; /* mb-6 */
             /* Initially hide all sections */
            display: none;
        }
         /* Show the active section */
        .content-section-card.active-section {
             display: block;
        }

        .content-section-card h2 {
             margin-bottom: 0.5rem; /* Add some space below h2 if it's directly followed by content */
        }

        /* Ensure buttons in modals and other areas have consistent styling */
        .btn-primary {
            background-color: #56c8d8; /* Your primary color */
            color: white;
            padding: 0.5rem 1rem; /* py-2 px-4 */
            border-radius: 0.5rem; /* rounded-lg */
            transition: background-color 0.2s ease;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #45b1c0; /* Darker shade of primary */
        }
        .btn-secondary {
            background-color: #e5e7eb; /* Tailwind gray-200 */
            color: #374151; /* Tailwind gray-700 */
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s ease;
            font-weight: 500;
        }
        .btn-secondary:hover {
            background-color: #d1d5db; /* Darker gray */
        }

        /* Table styling adjustments */
        .orders-table th {
            background-color: #f9fafb; /* bg-gray-50 */
        }
        .orders-table td, .orders-table th {
            /* border-bottom: 1px solid #e5e7eb; Tailwind divide-gray-200 */
        }
        .orders-table tr:last-child td {
            border-bottom: none;
        }

        /* Wishlist/Likes item card */
        .like-item-card {
            background-color: #ffffff;
            border-radius: 0.5rem; /* rounded-lg */
            border: 1px solid #e5e7eb; /* border-gray-200 */
            transition: box-shadow 0.3s ease;
        }
        .like-item-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); /* shadow-lg */
        }
        /* Modal backdrop and animation */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 100; /* Ensure it's above page content but below modal */
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .modal-backdrop.visible {
            opacity: 1;
        }
         .modal-content {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            position: relative;
            transform: scale(0.95);
            transition: transform 0.3s ease-in-out;
        }
        .modal-backdrop.visible .modal-content {
            transform: scale(1);
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include("navbar.php"); // Ensure navbar.php styling is compatible or updated ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php
        // Display messages for profile updates or like removals
        // These messages should ideally be handled via session and displayed once
        $message_type = $_GET['message'] ?? null;
        $error_type = $_GET['error'] ?? null;

        if ($message_type === 'removed'): ?>
            <div id="removeSuccessMessage" class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-md shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-700">Product removed from Likes successfully!</p>
                    </div>
                </div>
            </div>
        <?php elseif ($error_type): ?>
            <div id="removeErrorMessage" class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-md shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                         <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-700">
                            <?php
                            if ($error_type === 'invalid_product_id') echo 'Error: Invalid product ID.';
                            elseif ($error_type === 'db_connect_failed') echo 'Error: Could not connect to database.';
                            elseif ($error_type === 'prepare_failed') echo 'Error: Database query failed.';
                            elseif ($error_type === 'delete_failed') echo 'Error: Failed to remove product from Likes.';
                             elseif ($error_type === 'username_taken') echo 'Username already taken. Please choose another.';
                            elseif ($error_type === 'email_taken') echo 'Email already taken. Please choose another.';
                            elseif ($error_type === 'update_failed') echo 'An error occurred while updating your account.';
                            elseif ($error_type === 'otp_invalid') echo 'Invalid or expired OTP. Please try again.';
                            elseif ($error_type === 'otp_resend') echo 'OTP sent! Check your email.';
                            else echo 'An unknown error occurred.';
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="account-grid">
            <aside class="lg:sticky lg:top-8 self-start">
                <div class="bg-gray-200 rounded-2xl p-6 h-full shadow-sm">
                    <div class="flex flex-col items-center mb-8">
                        <div class="profile-initial-circle mb-4">
                            <?php
                            $firstLetter = !empty($user['username']) ? strtoupper(substr($user['username'], 0, 1)) : 'U';
                            $secondLetter = strlen($user['username']) > 1 ? strtoupper(substr($user['username'], 1, 1)) : '';
                            // Display one or two letters like "Aa" if available
                            echo htmlspecialchars($firstLetter . ($secondLetter ?: ''));
                            ?>
                        </div>
                        <h2 class="text-2xl font-semibold text-gray-800">
                            <?= htmlspecialchars($user['username'] ?? 'Guest User') ?>
                        </h2>
                    </div>
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500 font-medium mb-1">Username</dt>
                            <dd class="text-gray-800 text-base break-words"><?= htmlspecialchars($user['username'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 font-medium mb-1">First Name</dt>
                            <dd class="text-gray-800 text-base"><?= htmlspecialchars($user['fname'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Last Name</dt>
                            <dd class="text-gray-800 text-base"><?= htmlspecialchars($user['lname'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Phone Number</dt>
                            <dd class="text-gray-800 text-base"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Email Address</dt>
                            <dd class="text-gray-800 text-base break-words"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Member Since</dt>
                            <dd class="text-gray-800 text-base">
                                <?= isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A' ?>
                            </dd>
                        </div>
                    </dl>
                </div>
            </aside>

            <main class="min-w-0">
                <nav id="pill-navigation" class="pill-nav-container mb-8">
                    <button class="pill-nav-link" data-section="orders">Orders</button>
                    <button class="pill-nav-link" data-section="likes">Likes</button>
                    <button class="pill-nav-link" data-section="edit-profile">Edit Profile</button>
                    <button class="pill-nav-link" data-section="security">Security</button>
                </nav>

                <section id="orders" class="content-section-card">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl lg:text-2xl font-semibold text-gray-900">Order History</h2>
                    </div>
                    <p class="text-gray-600 mt-1 mb-6 text-sm lg:text-base">
                        Review your recent purchases and their current status.
                    </p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 orders-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product(s)</th>
                                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 lg:px-6 py-4 whitespace-normal text-sm font-medium text-gray-900 max-w-xs break-words">
                                                <?= htmlspecialchars($order['product_name']) ?>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $statusColorMapping = [
                                                    'cancelled' => 'red', 'failed' => 'red',
                                                    'shipped' => 'blue', 'delivered' => 'green',
                                                    'pending' => 'yellow', 'processing' => 'indigo',
                                                    'completed' => 'green' // Added completed status
                                                ];
                                                $statusColor = $statusColorMapping[strtolower($order['order_status'])] ?? 'gray';
                                                ?>
                                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-<?= $statusColor ?>-100 text-<?= $statusColor ?>-800">
                                                    <?= ucfirst(htmlspecialchars($order['order_status'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                                <?= number_format($order['total_price'], 2) ?> DA
                                            </td>
                                            <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <?php if (strtolower($order['order_status']) === 'processing' || strtolower($order['order_status']) === 'pending'): ?>
                                                    <form action="cancel_order.php" method="POST" class="inline">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <button type="submit"
                                                            class="text-red-600 hover:text-red-800 text-sm font-medium"
                                                            onclick="return confirm('Are you sure you want to cancel this order?')">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                <?php elseif (strtolower($order['order_status']) === 'completed'): ?>
                                                    <a href="review.php?order_id=<?= $order['id'] ?>"
                                                       class="text-turquoise-primary hover:text-cyan-700 text-sm font-medium">
                                                        Leave Review
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-sm italic">No actions</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-10 text-gray-500">
                                            You have no orders yet.
                                            <a href="products.php" class="text-indigo-600 hover:text-indigo-800 font-medium">Start shopping!</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section id="likes" class="content-section-card">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl lg:text-2xl font-semibold text-gray-900">Your Likes</h2>
                    </div>
                     <p class="text-gray-600 mt-1 mb-6 text-sm lg:text-base">
                        Items you've saved for later.
                    </p>
                    <?php if (!empty($likes)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($likes as $item): ?>
                                <div class="like-item-card overflow-hidden">
                                    <a href="product.php?id=<?= $item['id'] ?>" class="block">
                                        <?php
                                        $imagePath = $item['image'] ?? 'no-image.jpg';
                                        $finalImageSrc = 'uploads/' . htmlspecialchars($imagePath);

                                        // Fallback for missing image or if uploads/ is not correct
                                        if (!file_exists('uploads/' . $imagePath) || is_dir('uploads/' . $imagePath)) {
                                             $finalImageSrc = 'https://placehold.co/600x400/cccccc/969696?text=No+Image';
                                        }
                                        ?>
                                        <img src="<?= $finalImageSrc ?>"
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             class="w-full h-56 object-cover transition-transform duration-300 hover:scale-105"
                                             onerror="this.onerror=null;this.src='https://placehold.co/600x400/cccccc/969696?text=No+Image';">
                                    </a>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-lg text-gray-800 truncate" title="<?= htmlspecialchars($item['name']) ?>">
                                            <a href="product.php?id=<?= $item['id'] ?>" class="hover:text-[#56c8d8]">
                                                <?= htmlspecialchars($item['name']) ?>
                                            </a>
                                        </h3>
                                        <div class="mt-3 flex justify-between items-center">
                                            <a href="remove_from_wishlist.php?id=<?= $item['id'] ?>&return_url=<?= urlencode($_SERVER['REQUEST_URI']) ?>#likes"
                                               class="px-3 py-1.5 bg-red-500 text-white rounded-md hover:bg-red-600 text-xs font-medium transition-colors"
                                               title="Remove from Likes">
                                                Remove
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                         <div class="text-center py-10 text-gray-500">
                            You haven't liked any items yet.
                            <a href="products.php" class="text-indigo-600 hover:text-indigo-800 font-medium">Browse products to find gifts you love!</a>
                        </div>
                    <?php endif; ?>
                </section>

                <section id="edit-profile" class="content-section-card">
                     <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                        <div>
                            <h2 class="text-xl lg:text-2xl font-semibold text-gray-900">Profile Information</h2>
                            <p class="text-gray-600 mt-1 text-sm lg:text-base">Manage your personal details and account information.</p>
                        </div>
                        <button class="btn-primary mt-4 lg:mt-0 shrink-0" onclick="openEditProfileModal(event)">
                            Edit Profile
                        </button>
                    </div>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Username</dt>
                            <dd class="mt-1 text-gray-900 text-base break-words"><?= htmlspecialchars($user['username'] ?? 'N/A') ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">First Name</dt>
                            <dd class="mt-1 text-gray-900 text-base"><?= htmlspecialchars($user['fname'] ?? 'N/A') ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Last Name</dt>
                            <dd class="mt-1 text-gray-900 text-base"><?= htmlspecialchars($user['lname'] ?? 'N/A') ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                            <dd class="mt-1 text-gray-900 text-base"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Email address</dt>
                            <dd class="mt-1 text-gray-900 text-base break-words"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Member since</dt>
                            <dd class="mt-1 text-gray-900 text-base">
                                <?= isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A' ?>
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Email Address Settings</h3>
                                <p class="text-gray-600 mt-1 text-sm">Change your email address and verify it with an OTP.</p>
                            </div>
                            <button class="btn-primary mt-4 lg:mt-0 shrink-0" onclick="openChangeEmailModal(event)">
                                Change Email Address
                            </button>
                        </div>
                    </div>
                </section>

                <section id="security" class="content-section-card">
                     <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-xl lg:text-2xl font-semibold text-gray-900">Security Settings</h2>
                            <p class="text-gray-600 mt-1 text-sm lg:text-base">Update your password to keep your account secure.</p>
                        </div>
                         <button class="btn-primary mt-4 lg:mt-0 shrink-0" onclick="openChangePasswordModal(event)">
                            Change Password
                        </button>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="editProfileModal" class="modal-backdrop hidden">
        <div class="modal-content">
            <button onclick="closeEditProfileModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Update Account Information</h2>
            <form method="POST" action="update_account.php" class="space-y-4">
                <input type="hidden" name="user_id" value="<?= $_SESSION['id'] ?>">
                <div>
                    <label for="username_modal" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input id="username_modal" type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name_modal" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input id="first_name_modal" type="text" name="first_name" value="<?= htmlspecialchars($user['fname'] ?? '') ?>" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5">
                    </div>
                    <div>
                        <label for="last_name_modal" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input id="last_name_modal" type="text" name="last_name" value="<?= htmlspecialchars($user['lname'] ?? '') ?>" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5">
                    </div>
                </div>
                <div>
                    <label for="phone_modal" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input id="phone_modal" type="tel" name="phone_number" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5" pattern="[0-9]{10,15}" placeholder="e.g., 0512345678">
                </div>
                <button type="submit" class="w-full btn-primary py-2.5 text-sm font-semibold">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <div id="changeEmailModal" class="modal-backdrop hidden">
        <div class="modal-content">
            <button onclick="closeChangeEmailModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Change Email Address</h2>
            <p class="text-sm text-gray-500 mb-6">Enter your new email address to receive a verification code.</p>

            <form id="changeEmailForm" class="space-y-4">
                <div>
                    <label for="new_email_modal" class="block text-sm font-medium text-gray-700 mb-1">New Email</label>
                    <input id="new_email_modal" type="email" name="new_email" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5"
                        placeholder="Enter your new email address">
                </div>
                <div class="flex items-center justify-between pt-2">
                    <label for="email_otp_modal" class="text-sm font-medium text-gray-700">OTP Code</label>
                    <button type="button" id="sendEmailOtpButton" onclick="sendOTPForEmailChange(event)"
                        class="text-sm text-[#56c8d8] hover:underline font-medium">Send OTP</button>
                </div>
                <input id="email_otp_modal" type="text" name="otp" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5"
                    placeholder="Enter OTP received by email">
                <div id="emailOtpMessage" class="text-sm mt-1"></div>

                <button type="submit" class="w-full btn-primary py-2.5 text-sm font-semibold">
                    Verify & Change Email
                </button>
            </form>
        </div>
    </div>

    <div id="changePasswordModal" class="modal-backdrop hidden">
        <div class="modal-content">
            <button onclick="closeChangePasswordModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">🔐 Change Password</h2>
            <p class="text-sm text-gray-500 mb-6">Ensure your account is secure with a strong password.</p>

            <form id="changePasswordForm" method="POST" action="verify_change_password.php" class="space-y-4">
                <input type="hidden" name="userid" value="<?= $_SESSION['id'] ?>">
                <div>
                    <label for="email_password_modal" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="email_password_modal" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Enter your email" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5">
                </div>
                <div>
                    <label for="new_password_modal" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input id="new_password_modal" type="password" name="new_password" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5" placeholder="Enter new password">
                </div>
                <div>
                    <label for="confirm_password_modal" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input id="confirm_password_modal" type="password" name="confirm_password" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5" placeholder="Confirm new password">
                </div>
                <div class="flex items-center justify-between pt-2">
                    <label for="otp_modal" class="text-sm font-medium text-gray-700">OTP Code</label>
                    <button type="button" id="sendOtpButton" onclick="sendOTP()" class="text-sm text-[#56c8d8] hover:underline font-medium">Send OTP</button>
                </div>
                <input id="otp_modal" type="text" name="otp" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-[#56c8d8] focus:border-[#56c8d8] sm:text-sm p-2.5" placeholder="Enter OTP received by email">
                <div id="otpMessage" class="text-sm mt-1"></div>


                <button type="submit" class="w-full btn-primary py-2.5 text-sm font-semibold">
                    Update Password
                </button>
            </form>
        </div>
    </div>


    <?php include("footer.php"); ?>

    <script>
        // --- Section Toggling Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const navButtons = document.querySelectorAll('#pill-navigation button');
            const contentSections = document.querySelectorAll('main section[id]');

            // Function to show a specific section and hide others
            function showSection(sectionId) {
                contentSections.forEach(section => {
                    if (section.id === sectionId) {
                        section.classList.add('active-section');
                         section.style.display = 'block'; // Ensure it's displayed
                    } else {
                        section.classList.remove('active-section');
                        section.style.display = 'none'; // Hide other sections
                    }
                });
            }

            // Function to set the active class on the correct navigation button
            function setActiveNavButton(sectionId) {
                navButtons.forEach(button => {
                    if (button.dataset.section === sectionId) {
                        button.classList.add('active');
                    } else {
                        button.classList.remove('active');
                    }
                });
            }

            // Handle clicks on navigation buttons
            navButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const sectionId = button.dataset.section;
                    showSection(sectionId);
                    setActiveNavButton(sectionId);

                    // Update URL hash to reflect active section (optional, but good for direct linking)
                    history.pushState(null, '', `#${sectionId}`);
                });
            });

            // Read hash from URL on page load and activate corresponding section
            const initialHash = window.location.hash.substring(1); // Remove '#'
            if (initialHash && document.getElementById(initialHash)) {
                showSection(initialHash);
                setActiveNavButton(initialHash);
            } else {
                // Default to 'orders' section if no hash or invalid hash
                showSection('orders');
                setActiveNavButton('orders');
            }

            // Listen for hash changes (e.g., browser back/forward)
            window.addEventListener('hashchange', () => {
                const currentHash = window.location.hash.substring(1);
                if (currentHash && document.getElementById(currentHash)) {
                    showSection(currentHash);
                    setActiveNavButton(currentHash);
                }
            });


            // --- Modal Logic ---
            const editProfileModal = document.getElementById('editProfileModal');
            const changeEmailModal = document.getElementById('changeEmailModal');
            const changePasswordModal = document.getElementById('changePasswordModal');

            function openModal(modal) {
                modal.classList.remove('hidden');
                setTimeout(() => modal.classList.add('visible'), 10); // Trigger transition
                document.body.classList.add('overflow-hidden'); // Prevent scrolling
            }

            function closeModal(modal) {
                modal.classList.remove('visible');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden'); // Re-enable scrolling
                }, 300); // Wait for transition to finish
            }

            window.openEditProfileModal = function(event) {
                event.preventDefault();
                openModal(editProfileModal);
            }

            window.closeEditProfileModal = function() {
                closeModal(editProfileModal);
            }

            window.openChangeEmailModal = function(event) {
                event.preventDefault();
                openModal(changeEmailModal);
            }

            window.closeChangeEmailModal = function() {
                closeModal(changeEmailModal);
            }

            window.openChangePasswordModal = function(event) {
                event.preventDefault();
                openModal(changePasswordModal);
            }

            window.closeChangePasswordModal = function() {
                closeModal(changePasswordModal);
            }

            // Close modals if clicking outside (on the backdrop)
            editProfileModal.addEventListener('click', (e) => {
                if (e.target === editProfileModal) closeModal(editProfileModal);
            });
            changeEmailModal.addEventListener('click', (e) => {
                if (e.target === changeEmailModal) closeModal(changeEmailModal);
            });
            changePasswordModal.addEventListener('click', (e) => {
                if (e.target === changePasswordModal) closeModal(changePasswordModal);
            });


            // --- OTP & Email Change Logic (using PHP Sessions on backend) ---
            const sendEmailOtpButton = document.getElementById('sendEmailOtpButton');
            const emailOtpMessageDiv = document.getElementById('emailOtpMessage');
            const newEmailInput = document.getElementById('new_email_modal');
            const emailOtpInput = document.getElementById('email_otp_modal');
            const changeEmailForm = document.getElementById('changeEmailForm');

            // Function to send OTP for email change
            window.sendOTPForEmailChange = function(event) {
                event.preventDefault();
                const newEmail = newEmailInput.value;

                if (!newEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) {
                    emailOtpMessageDiv.textContent = 'Please enter a valid new email address.';
                    emailOtpMessageDiv.className = 'text-sm mt-1 text-red-600';
                    return;
                }

                sendEmailOtpButton.disabled = true;
                sendEmailOtpButton.textContent = 'Sending...';
                emailOtpMessageDiv.textContent = ''; // Clear previous messages

                fetch('send_email_verification_otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `new_email=${encodeURIComponent(newEmail)}`
                })
                .then(response => {
                    if (!response.ok) {
                         // Handle HTTP errors
                         throw new Error(`HTTP error! status: ${response.status}`);
                     }
                     return response.json(); // Expecting JSON response
                })
                .then(data => {
                    if (data.success) {
                        emailOtpMessageDiv.textContent = 'OTP sent! Check your email. It might be in spam.';
                        emailOtpMessageDiv.className = 'text-sm mt-1 text-green-600';
                    } else {
                        emailOtpMessageDiv.textContent = data.message || 'Failed to send OTP. Please try again.';
                        emailOtpMessageDiv.className = 'text-sm mt-1 text-red-600';
                         console.error("Send OTP Error: " + (data.message || 'Unknown error')); // Log server-side message
                    }
                })
                .catch(error => {
                    console.error('Error sending OTP:', error);
                    emailOtpMessageDiv.textContent = 'An error occurred. Failed to send OTP.';
                    emailOtpMessageDiv.className = 'text-sm mt-1 text-red-600';
                     console.error("Send OTP Fetch Error: " + error); // Log fetch error
                })
                .finally(() => {
                     sendEmailOtpButton.disabled = false;
                     sendEmailOtpButton.textContent = 'Resend OTP';
                });
            }

            // Handle submission of the email change form
            changeEmailForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent default form submission

                const enteredOtp = emailOtpInput.value;
                const newEmail = newEmailInput.value; // The new email is passed to the backend, but the backend will use the session's new_email_pending

                if (!enteredOtp) {
                    emailOtpMessageDiv.textContent = 'Please enter the OTP.';
                    emailOtpMessageDiv.className = 'text-sm mt-1 text-red-600';
                    return;
                }

                if (!newEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) {
                    // This case should ideally be caught by sendOTPForEmailChange, but as a fallback
                    emailOtpMessageDiv.textContent = 'Please provide the new email address.';
                    emailOtpMessageDiv.className = 'text-sm mt-1 text-red-600';
                    return;
                }

                // Disable button and show loading
                const submitButton = changeEmailForm.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Verifying...';
                emailOtpMessageDiv.textContent = ''; // Clear previous messages

                fetch('update_email_with_otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `otp=${encodeURIComponent(enteredOtp)}&new_email_submitted=${encodeURIComponent(newEmail)}` // Pass new email for backend consistency, though session is source
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        emailOtpMessageDiv.textContent = 'Email updated successfully!';
                        emailOtpMessageDiv.className = 'text-sm mt-1 text-green-600';
                        // Optionally, refresh the page or update the displayed email
                        setTimeout(() => {
                            closeChangeEmailModal();
                            window.location.reload(); // Reload to show updated email
                        }, 1500);
                    } else {
                        emailOtpMessageDiv.textContent = data.message || 'Failed to update email. Please try again.';
                        emailOtpMessageDiv.className = 'text-sm mt-1 text-red-600';
                        console.error("Verify & Change Email Error: " + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error during email change:', error);
                    emailOtpMessageDiv.textContent = 'An error occurred. Failed to update email.';
                    emailOtpMessageDiv.className = 'text-sm mt-1 text-red-600';
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Verify & Change Email';
                });
            });


            // --- OTP Password Change Logic (using PHP Sessions on backend for password change) ---
            const sendOtpButton = document.getElementById('sendOtpButton');
            const otpMessageDiv = document.getElementById('otpMessage');
            const emailPasswordInput = document.getElementById('email_password_modal');
            const changePasswordForm = document.getElementById('changePasswordForm');

            window.sendOTP = function() {
                const email = emailPasswordInput.value;

                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    otpMessageDiv.textContent = 'Please enter a valid email address.';
                    otpMessageDiv.className = 'text-sm mt-1 text-red-600';
                    return;
                }

                sendOtpButton.disabled = true;
                sendOtpButton.textContent = 'Sending...';
                otpMessageDiv.textContent = ''; // Clear previous messages

                fetch('send_password_reset_otp.php', { // This should be a separate endpoint for password OTP
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}`
                })
                .then(response => {
                    if (!response.ok) {
                         throw new Error(`HTTP error! status: ${response.status}`);
                     }
                     return response.json();
                })
                .then(data => {
                    if (data.success) {
                        otpMessageDiv.textContent = 'OTP sent! Check your email. It might be in spam.';
                        otpMessageDiv.className = 'text-sm mt-1 text-green-600';
                    } else {
                        otpMessageDiv.textContent = data.message || 'Failed to send OTP. Please try again.';
                        otpMessageDiv.className = 'text-sm mt-1 text-red-600';
                    }
                })
                .catch(error => {
                    console.error('Error sending OTP:', error);
                    otpMessageDiv.textContent = 'An error occurred. Failed to send OTP.';
                    otpMessageDiv.className = 'text-sm mt-1 text-red-600';
                })
                .finally(() => {
                     sendOtpButton.disabled = false;
                     sendOtpButton.textContent = 'Resend OTP';
                });
            }
        });
    </script>
</body>
</html>
