<?php
session_start();

if (!isset($_SESSION['userid'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$db = new mysqli("localhost", "root", "", "giftstore");

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$user_id = $_SESSION['id'];

// Fetch orders
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

$order_stmt->bind_param("i", $user_id);
$order_stmt->execute();
$orders = $order_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$order_stmt->close();

// Fetch user info
// Modify the SQL query to include new fields
$stmt = $db->prepare("SELECT username, email, fname, lname, phone, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Ensure custom grid works */
        @media (min-width: 1024px) {
            .account-grid {
                grid-template-columns: 256px 1fr;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include("navbar.php"); ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid gap-6 account-grid">
            <!-- Mobile-Optimized Sidebar -->
            <nav class="lg:order-first">
                <div class="bg-white rounded-xl shadow-sm p-4 mb-4 border border-gray-200">
                    <div class="lg:block">
                        <h2 class="text-lg font-semibold text-gray-800">Welcome back,</h2>
                        <p class="text-[#56c8d8] font-medium truncate"><?= htmlspecialchars($_SESSION['username']) ?>
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-2 space-y-1 border border-gray-200">
                    <a href="#profile"
                        class="flex items-center space-x-3 px-3 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <svg class="w-5 h-5 text-[#56c8d8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Profile</span>
                    </a>
                    <a href="#orders"
                        class="flex items-center space-x-3 px-3 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <svg class="w-5 h-5 text-[#56c8d8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Orders</span>
                    </a>
                    <a href="javascript:void(0)" onclick="openUpdateModal()"
                        class="flex items-center space-x-3 px-3 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <svg class="w-5 h-5 text-[#56c8d8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span>Edit Profile</span>
                    </a>
                    <a onclick="openChangePasswordModal()"
                        class="flex items-center space-x-3 px-3 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <svg class="w-5 h-5 text-[#56c8d8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        <span>Security</span>
                    </a>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="min-w-0 lg:order-last">
                <header class="mb-6">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                        Account Overview
                    </h1>
                    <p class="text-gray-500 mt-2 text-sm lg:text-base">Manage your profile and orders</p>
                </header>

                <!-- Profile Section -->
                <section id="profile" class="mb-6">
                    <?php if (isset($_GET['updated'])): ?>
                        <div class="bg-green-50 border-l-4 border-green-400 p-3 mb-4 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="text-green-700 text-sm lg:text-base">Account updated successfully!</span>
                            </div>
                        </div>
                    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'username_taken'): ?>
                        <div class="bg-red-50 border-l-4 border-red-400 p-3 mb-4 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="text-red-700 text-sm lg:text-base">Username already taken. Please choose
                                    another.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6 border border-gray-200">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4">
                            <div class="mb-3 lg:mb-0">
                                <h2 class="text-lg lg:text-xl font-semibold text-gray-900">Profile Information</h2>
                                <p class="text-gray-500 mt-1 text-sm lg:text-base">Basic details and account info</p>
                            </div>
                            <button onclick="openUpdateModal()"
                                class="btn-primary text-white px-4 py-2 rounded-lg w-full lg:w-auto text-sm lg:text-base">
                                Edit Profile
                            </button>
                        </div>

                        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Username</dt>
                                <dd class="mt-1 text-gray-900 break-words">
                                    <?= htmlspecialchars($_SESSION['username']) ?>
                                </dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">First Name</dt>
                                <dd class="mt-1 text-gray-900"><?= htmlspecialchars($user['fname'] ?? '—') ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Last Name</dt>
                                <dd class="mt-1 text-gray-900"><?= htmlspecialchars($user['lname'] ?? '—') ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                                <dd class="mt-1 text-gray-900"><?= htmlspecialchars($user['phone'] ?? '—') ?>
                                </dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Email address</dt>
                                <dd class="mt-1 text-gray-900 break-words"><?= htmlspecialchars($user['email']) ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Member since</dt>
                                <dd class="mt-1 text-gray-900"><?= date('F j, Y', strtotime($user['created_at'])) ?>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <!-- Orders Section -->
                <section id="orders" class="mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="px-4 py-4 lg:px-6 lg:py-5 border-b border-gray-200">
                            <h2 class="text-lg lg:text-xl font-semibold text-gray-900">Order History</h2>
                            <p class="text-gray-500 mt-1 text-sm lg:text-base">Recent purchases and status</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Product</th>
                                        <th
                                            class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date</th>
                                        <th
                                            class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status</th>
                                        <th
                                            class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td
                                                class="px-4 lg:px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                                
                                                <div>
                                                    <?= htmlspecialchars($order['product_name']) ?>
                                                </div>
                                            </td>

                                            <td class="px-4 lg:px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                            </td>
                                            <td class="px-4 lg:px-6 py-3 whitespace-nowrap">
                                                <?php
                                                $statusColor = [
                                                    'cancelled' => 'red',
                                                    'shipped' => 'green',
                                                    'delivered' => 'green',
                                                    'pending' => 'yellow'
                                                ][$order['order_status']] ?? 'gray';
                                                ?>
                                                <span
                                                    class="px-2.5 py-1 rounded-full text-xs font-medium bg-<?= $statusColor ?>-100 text-<?= $statusColor ?>-800">
                                                    <?= ucfirst($order['order_status']) ?>
                                                </span>
                                            </td>
                                            <td
                                                class="px-4 lg:px-6 py-3 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                                <?= number_format($order['total_price'], 2) ?> DA
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Update Modal -->
    <div id="updateModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden items-center justify-center p-4"
        onclick="closeUpdateModal(event)">
        <div class="bg-white w-full max-w-md mx-auto p-4 lg:p-6 rounded-lg shadow-lg relative"
            onclick="event.stopPropagation()">
            <button onclick="closeUpdateModal()"
                class="absolute top-2 right-3 text-gray-500 hover:text-red-500 text-2xl">&times;</button>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Update Account Info</h2>
            <form method="POST" action="update_account.php" class="flex flex-col gap-3 lg:gap-4">
                <input type="hidden" name="user_id" value="<?= $_SESSION['id'] ?>">
                <div>
                    <label class="block text-sm lg:text-base font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                        class="w-full border px-3 py-2 rounded text-sm lg:text-base" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm lg:text-base font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($user['fname']) ?>"
                            class="w-full border px-3 py-2 rounded text-sm lg:text-base">
                    </div>
                    <div>
                        <label class="block text-sm lg:text-base font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($user['lname']) ?>"
                            class="w-full border px-3 py-2 rounded text-sm lg:text-base">
                    </div>
                </div>
                <div>
                    <label class="block text-sm lg:text-base font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" name="phone_number" value="<?= htmlspecialchars($user['phone']) ?>"
                        class="w-full border px-3 py-2 rounded text-sm lg:text-base" pattern="[0-9]{10}">
                </div>
                <div>
                    <label class="block text-sm lg:text-base font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                        class="w-full border px-3 py-2 rounded text-sm lg:text-base" required>
                </div>
                <button type="submit"
                    class="bg-[#56c8d8] hover:bg-[#45b1c0] text-white px-4 py-2 rounded mt-2 text-sm lg:text-base">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal"
        class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
            <button onclick="closeChangePasswordModal()"
                class="absolute top-3 right-3 text-gray-500 hover:text-red-500 text-2xl">&times;</button>
            <h2 class="text-xl font-bold mb-4 text-gray-800">🔐 Change Password</h2>

            <form id="changePasswordForm" method="POST" action="verify_change_password.php" class="space-y-4">
                <input type="hidden" name="userid" value="<?= $_SESSION['id'] ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full border rounded px-3 py-2"
                        placeholder="Enter your email" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" name="new_password" required class="w-full border rounded px-3 py-2"
                        placeholder="New password" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full border rounded px-3 py-2"
                        placeholder="Confirm new password" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-600">OTP Code</label>
                    <button type="button" onclick="sendOTP()" class="text-sm text-[#56c8d8] hover:underline">Send
                        OTP</button>
                </div>
                <input type="text" name="otp" required class="w-full border rounded px-3 py-2"
                    placeholder="Enter OTP" />

                <button type="submit" class="bg-[#56c8d8] hover:bg-[#45b1c0] text-white px-4 py-2 rounded w-full">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <script>
        function openUpdateModal() {
            document.getElementById('updateModal').classList.remove('hidden');
        }
        function closeUpdateModal(e) {
            if (!e || e.target.id === "updateModal") {
                document.getElementById('updateModal').classList.add('hidden');
            }
        }
        // Auto-dismiss messages after 3 seconds
        setTimeout(() => {
            document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]').forEach(el => el.remove());
        }, 3000);

        function openChangePasswordModal() {
            document.getElementById('changePasswordModal').classList.remove('hidden');
        }
        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').classList.add('hidden');
        }
        const email = document.querySelector('[name="email"]').value;

        fetch('send_otp_phpmailer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'email=' + encodeURIComponent(email)
        });

    </script>
</body>

</html>