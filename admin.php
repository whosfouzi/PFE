<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}


$db = new mysqli("localhost", "root", "", "giftstore");
$users = $db->query("SELECT id, fname, lname, username, email, role FROM users ORDER BY role, id");

// Handle sorting
$validSorts = ['price', 'stock'];
$sort = in_array($_GET['sort'] ?? '', $validSorts) ? $_GET['sort'] : 'id';
$order = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Handle filters
$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build base query
$conditions = [];
$params = [];
$types = '';

if (!empty($category_filter)) {
  $conditions[] = "category = ?";
  $params[] = $category_filter;
  $types .= 's';
}
if (!empty($search_query)) {
  $conditions[] = "name LIKE ?";
  $params[] = "%$search_query%";
  $types .= 's';
}
$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Count total products (for pagination)
$count_sql = "SELECT COUNT(*) FROM products $where";
$count_stmt = $db->prepare($count_sql);
if (!empty($params)) {
  $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_stmt->bind_result($total_products);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_products / $limit);

// Fetch paginated products
$sql = "SELECT id, name, category, price, stock FROM products $where ORDER BY $sort $order LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
if (!empty($params)) {
  $types_with_pagination = $types . 'ii';
  $params_with_pagination = array_merge($params, [$limit, $offset]);
  $stmt->bind_param($types_with_pagination, ...$params_with_pagination);
} else {
  $stmt->bind_param("ii", $limit, $offset);
}

$category_counts = [];
$result = $db->query("SELECT category, COUNT(*) as total FROM products GROUP BY category");
while ($row = $result->fetch_assoc()) {
  $category_counts[$row['category']] = (int) $row['total'];
}

$stmt->execute();
$products = $stmt->get_result();

// Get category list
$categories = $db->query("SELECT DISTINCT category FROM products ORDER BY category ASC");

// Fetch orders (pagination + item count + status)
$order_page = max(1, intval($_GET['order_page'] ?? 1));
$order_limit = 10;
$order_offset = ($order_page - 1) * $order_limit;
$order_sort = ($_GET['order_sort'] ?? 'created_at');
$order_dir = ($_GET['order_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Total number of orders
$order_total_query = $db->query("SELECT COUNT(*) as total FROM orders");
$order_total = $order_total_query->fetch_assoc()['total'];
$order_total_pages = ceil($order_total / $order_limit);

// Fetch paginated orders
$order_sql = "
  SELECT o.id, o.user_name, o.email, o.total_price, o.order_status, o.created_at, COUNT(oi.id) as item_count
  FROM orders o
  LEFT JOIN order_items oi ON o.id = oi.order_id
  GROUP BY o.id
  ORDER BY o.$order_sort $order_dir
  LIMIT $order_limit OFFSET $order_offset
";
$orders = $db->query($order_sql);

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>GiftStore Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }

    .section {
      display: none;
    }

    .section.active {
      display: block;
    }
  </style>
</head>

<body class="bg-grey-500">
  <div class="flex h-screen">
    <aside class="w-full lg:w-64 bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-4">
      <div class="px-3 py-4 border-b border-gray-100">
        <h2 class="text-xl font-semibold text-[#56c8d8] flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18" />
          </svg>
          Admin Panel
        </h2>
      </div>

      <nav class="flex flex-col gap-2">
        <button onclick="showSection('dashboard')"
          class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg transition">
          <span>Dashboard</span>
        </button>

        <button onclick="showSection('users')"
          class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg transition">
          <span>Manage Users</span>
        </button>

        <button onclick="showSection('products')"
          class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg transition">
          <span>Manage Products</span>
        </button>

        <button onclick="showSection('orders')"
          class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg transition">
          <span>Manage Orders</span>
        </button>

        <button onclick="showSection('payments')"
          class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg transition">
          <span>Manage Payments</span>
        </button>

        <button onclick="showSection('reviews')"
          class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-lg transition">
          <span>Manage Reviews</span>
        </button>

        <a href="logout.php"
          class="flex items-center space-x-3 px-3 py-2 text-red-500 hover:text-red-600 rounded-lg transition">
          <span>Logout</span>
        </a>
      </nav>
    </aside>


    <!-- Main content -->
    <main class="flex-1 p-10 overflow-y-auto">
      <section id="dashboard" class="section active mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
            Welcome, Admin 👋
          </h1>
          <p class="text-gray-500 text-sm lg:text-base">
            Here’s what you can manage today.
          </p>
        </div>
      </section>


      <!-- Users Section -->
      <section id="users" class="section mb-6">
        <?php if (isset($_GET['added'])): ?>
          <div id="successMessage"
            class="bg-green-50 border-l-4 border-green-400 p-4 mb-4 rounded-lg shadow-sm transition-opacity duration-500">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clip-rule="evenodd" />
              </svg>
              <span class="text-green-800 text-sm">Delivery person added successfully!</span>
            </div>
          </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
          <h2 class="text-xl lg:text-2xl font-semibold text-gray-900 mb-4">Manage Users</h2>
          <p class="text-gray-500 text-sm mb-4">View, promote, or remove users. You can also add new delivery persons
            below.</p>

          <!-- Add Delivery Person Modal -->
          <div id="deliveryModal"
            class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-xl w-full max-w-2xl p-6 shadow-lg relative">
              <button onclick="closeDeliveryModal()"
                class="absolute top-3 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>

              <h2 class="text-xl font-semibold text-gray-800 mb-4">Add Delivery Person</h2>

              <form action="add_delivery_person.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                  <input type="text" name="fname" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                  <input type="text" name="lname" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                  <input type="text" name="username" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                  <input type="text" name="phone" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                  <input type="password" name="password" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <input type="hidden" name="role" value="delivery person">

                <div class="col-span-1 md:col-span-2 text-right mt-2">
                  <button type="submit"
                    class="bg-[#56c8d8] text-white px-5 py-2 rounded-lg hover:bg-[#3db9c7] transition">
                    Add Delivery Person
                  </button>
                </div>
              </form>
            </div>
          </div>
          <button onclick="openDeliveryModal()"
            class="bg-[#56c8d8] text-white px-4 py-2 rounded-lg hover:bg-[#3db9c7] transition mb-6">
            + Add Delivery Person
          </button>


          <!-- Users Table -->
          <div class="overflow-auto rounded-lg border border-gray-200">
            <table class="min-w-full bg-white divide-y divide-gray-100 text-sm">
              <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold tracking-wider">
                <tr>
                  <th class="px-4 py-3 text-left">#</th>
                  <th class="px-4 py-3 text-left">Full Name</th>
                  <th class="px-4 py-3 text-left">Username</th>
                  <th class="px-4 py-3 text-left">Email</th>
                  <th class="px-4 py-3 text-left">Role</th>
                  <th class="px-4 py-3 text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php while ($row = $users->fetch_assoc()): ?>
                  <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-800"><?= $row['id'] ?></td>
                    <td class="px-4 py-3 text-gray-800"><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
                    <td class="px-4 py-3 text-gray-800"><?= htmlspecialchars($row['username']) ?></td>
                    <td class="px-4 py-3 text-gray-800"><?= htmlspecialchars($row['email']) ?></td>
                    <td class="px-4 py-3 text-gray-800"><?= ucfirst($row['role']) ?></td>
                    <td class="px-4 py-3 text-center">
                      <?php if ($row['role'] === 'client' || $row['role'] === 'delivery person'): ?>
                        <a href="promote_user.php?id=<?= $row['id'] ?>" class="text-[#56c8d8] hover:underline">Promote</a>
                        <a href="delete_user.php?id=<?= $row['id'] ?>" class="ml-4 text-red-500 hover:underline">Delete</a>
                      <?php else: ?>
                        <span class="text-gray-400">No actions</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Products Section -->
      <section id="products" class="section mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
          <h2 class="text-xl lg:text-2xl font-semibold text-gray-900 mb-4">Manage Products</h2>
          <p class="text-gray-500 text-sm mb-6">Browse, edit, and manage product listings. Use filters or search to
            refine.</p>

          <?php if (isset($_GET['updated'])): ?>
            <div
              class="flex items-center gap-2 px-4 py-3 bg-green-50 border-l-4 border-green-400 rounded-lg mb-4 shadow-sm">
              <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-4h2v2h-2v-2zm0-8h2v6h-2V6z"
                  clip-rule="evenodd" />
              </svg>
              <span class="text-green-800 text-sm">Product updated successfully!</span>
            </div>
          <?php elseif (isset($_GET['deleted'])): ?>
            <div class="mb-4 px-4 py-2 bg-red-100 text-red-800 rounded">
              🗑️ Product deleted successfully!
            </div>
          <?php endif; ?>

          <!-- Chart Container -->
          <div class="mb-10 bg-gray-50 p-4 rounded-lg border border-gray-200 max-w-4xl mx-auto">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Products by Category</h3>
            <canvas id="categoryChart" height="100"></canvas>
          </div>

          <!-- Filter & Search -->
          <div class="mb-6">
            <div class="flex flex-wrap items-center gap-4">

              <!-- Category Filter -->
              <form method="GET" class="flex items-center gap-2">
                <label for="category" class="text-gray-700 font-medium whitespace-nowrap">Filter by category:</label>
                <select name="category" onchange="this.form.submit()" class="border rounded px-4 py-2">
                  <option value="">All Categories</option>
                  <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $cat['category'] === $category_filter ? 'selected' : '' ?>>
                      <?= htmlspecialchars($cat['category']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </form>

              <!-- Search Bar -->
              <form method="GET" class="flex items-center gap-2">
                <input type="text" name="search"
                  value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                  placeholder="Search by name..." class="border px-4 py-2 rounded">
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">Search</button>
              </form>

            </div>
          </div>
          <!-- Chart.js -->
          <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
          <script>
            document.addEventListener('DOMContentLoaded', function () {
              const ctx = document.getElementById('categoryChart').getContext('2d');
              const categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                  labels: <?= json_encode(array_keys($category_counts)) ?>,
                  datasets: [{
                    label: 'Products',
                    data: <?= json_encode(array_values($category_counts)) ?>,
                    backgroundColor: '#56c8d8',
                    borderColor: '#56c8d8',
                    borderWidth: 1
                  }]
                },
                options: {
                  scales: {
                    y: { beginAtZero: true }
                  }
                }
              });
            });
          </script>

          <!-- Add Product Button -->
          <div class="flex justify-end mb-4">
            <button onclick="openAddModal()"
              class="bg-[#56c8d8] text-white px-4 py-2 rounded-lg hover:bg-[#3db9c7] transition">
              + Add Product
            </button>
          </div>

          <!-- Products Table -->
          <div class="overflow-auto rounded-lg border border-gray-200">
            <table class="min-w-full bg-white text-sm divide-y divide-gray-100">
              <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold tracking-wider">
                <tr>
                  <th class="px-4 py-3 text-left">#</th>
                  <th class="px-4 py-3 text-left">Name</th>
                  <th class="px-4 py-3 text-left">Category</th>
                  <th class="px-4 py-3 text-left">Price</th>
                  <th class="px-4 py-3 text-left">Stock</th>
                  <th class="px-4 py-3 text-left">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php $i = 1;
                $totalStock = 0; ?>
                <?php while ($prod = $products->fetch_assoc()): ?>
                  <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-800"><?= $i++ ?></td>
                    <td class="px-4 py-3 text-gray-800"><?= htmlspecialchars($prod['name']) ?></td>
                    <td class="px-4 py-3 text-gray-800"><?= htmlspecialchars($prod['category']) ?></td>
                    <td class="px-4 py-3 text-gray-800">DA <?= number_format($prod['price'], 2) ?></td>
                    <td class="px-4 py-3 text-gray-800">
                      <?php
                      $stock = $prod['stock'];
                      $badgeColor = $stock == 0 ? 'bg-red-500' : ($stock < 5 ? 'bg-yellow-400' : 'bg-green-500');
                      $badgeLabel = $stock == 0 ? 'Out of Stock' : ($stock < 5 ? 'Low' : 'In Stock');
                      $totalStock += $stock;
                      ?>
                      <span class="inline-block px-2 py-1 rounded-full text-white text-xs <?= $badgeColor ?>">
                        <?= $stock ?> – <?= $badgeLabel ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-gray-800">
                      <button onclick="openEditModal(<?= htmlspecialchars(json_encode($prod)) ?>)"
                        class="text-blue-500 hover:underline text-sm">Edit</button>
                      <button onclick="openDeleteModal(<?= $prod['id'] ?>, '<?= htmlspecialchars($prod['name']) ?>')"
                        class="text-red-500 hover:underline text-sm ml-4">Delete</button>
                    </td>
                  </tr>
                <?php endwhile; ?>
                <tr class="bg-gray-50 font-semibold">
                  <td class="px-4 py-3 text-gray-700" colspan="1">Total:</td>
                  <td class="px-4 py-3 text-gray-700" colspan="3">
                    <?= $i - 1 ?> product<?= ($i - 1) > 1 ? 's' : '' ?>
                  </td>
                  <td class="px-4 py-3 text-gray-700"><?= $totalStock ?></td>
                  <td></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center gap-2 text-sm font-medium">
              <?php
              $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
              $query = $_GET;
              ?>

              <?php if ($page > 1): ?>
                <?php $query['page'] = $page - 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">« Prev</a>
              <?php endif; ?>

              <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php $query['page'] = $p; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-3 py-1 rounded <?= $p == $page ? 'bg-[#56c8d8] text-white' : 'bg-gray-100 hover:bg-gray-200' ?>">
                  <?= $p ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $total_pages): ?>
                <?php $query['page'] = $page + 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next »</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- Orders Section -->
      <section id="orders" class="section">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Manage Orders</h2>

        <div class="overflow-auto rounded-lg shadow bg-white">
          <table class="min-w-full text-sm text-gray-800">
            <thead class="bg-pink-100 text-pink-700">
              <tr>
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left">User</th>
                <th class="px-4 py-3 text-left">Email</th>
                <th class="px-4 py-3 text-left">Total</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php $i = 1 + ($page - 1) * $limit; ?>
              <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                  <td class="px-4 py-3"><?= $i++ ?></td>
                  <td class="px-4 py-3"><?= htmlspecialchars($order['user_name']) ?></td>
                  <td class="px-4 py-3"><?= htmlspecialchars($order['email']) ?></td>
                  <td class="px-4 py-3">€<?= number_format($order['total_price'], 2) ?></td>
                  <td class="px-4 py-3">
                    <?php
                    $badgeColor = match ($order['order_status']) {
                      'shipped' => 'bg-green-500',
                      'cancelled' => 'bg-red-500',
                      default => 'bg-yellow-400'
                    };
                    ?>
                    <span class="px-2 py-1 text-white text-xs rounded-full <?= $badgeColor ?>">
                      <?= ucfirst($order['order_status']) ?>
                    </span>
                  </td>
                  <td class="px-4 py-3"><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                  <td class="px-4 py-3">
                    <button onclick="openOrderDetailsModal(<?= $order['id'] ?>)"
                      class="text-blue-500 hover:underline text-sm">View</button>
                    <?php if ($order['order_status'] !== 'cancelled'): ?>
                      <button onclick="openUpdateStatusModal(<?= $order['id'] ?>, '<?= $order['order_status'] ?>')"
                        class="ml-2 text-pink-600 hover:underline text-sm">Update</button>
                    <?php endif; ?>
                  </td>

                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </section>


      <section id="payments" class="section">
        <hlass="text-xl font-semibold text-gray-800 mb-4">Manage Payments</h2>
          <p class="text-gray-600">Coming soon...</p>
      </section>

      <section id="reviews" class="section">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Manage Reviews</h2>
        <p class="text-gray-600">Coming soon...</p>
      </section>
    </main>
  </div>

  <!-- Edit Product Modal -->
  <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg w-full max-w-md shadow-xl">
      <h3 class="text-xl font-semibold text-pink-600 mb-4">Edit Product</h3>
      <form id="editForm" method="POST" action="update_product.php">
        <input type="hidden" name="return_category" id="edit-return-category">
        <input type="hidden" name="id" id="edit-id">
        <div class="mb-4">
          <label class="block text-sm font-medium">Name:</label>
          <input type="text" name="name" id="edit-name" class="border rounded w-full px-3 py-2">
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Category:</label>
          <input type="text" name="category" id="edit-category" class="border rounded w-full px-3 py-2">
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Price:</label>
          <input type="text" name="price" id="edit-price" class="border rounded w-full px-3 py-2">
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Stock:</label>
          <input type="number" name="stock" id="edit-stock" class="border rounded w-full px-3 py-2">
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-pink-600 text-white rounded">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-xl">
      <h3 class="text-xl font-semibold text-red-600 mb-4">Delete Product</h3>
      <p id="delete-message" class="text-gray-700 mb-4"></p>
      <form method="POST" action="delete_product.php">
        <input type="hidden" name="id" id="delete-id">
        <input type="hidden" name="return_category" id="delete-return-category">
        <div class="flex justify-end gap-3">
          <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Product Modal -->
  <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg w-full max-w-md shadow-xl">
      <h3 class="text-xl font-semibold text-pink-600 mb-4">Add Product</h3>
      <form method="POST" action="add_product.php" enctype="multipart/form-data">
        <div class="mb-4">
          <label class="block text-sm font-medium">Name:</label>
          <input type="text" name="name" class="border rounded w-full px-3 py-2" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Category:</label>
          <input type="text" name="category" class="border rounded w-full px-3 py-2" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Description:</label>
          <input type="text" name="description" class="border rounded w-full px-3 py-2" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Price:</label>
          <input type="text" name="price" class="border rounded w-full px-3 py-2" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Stock:</label>
          <input type="number" name="stock" class="border rounded w-full px-3 py-2" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium">Image:</label>
          <input type="file" name="image" accept="image/*" class="border rounded w-full px-3 py-2" required>
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-pink-600 text-white rounded">Add</button>
        </div>
      </form>
    </div>
  </div>

  <!-- View Order Modal -->
  <div id="viewOrderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white p-6 rounded-lg w-full max-w-lg shadow-lg">
      <h3 class="text-lg font-semibold mb-4">Order Details</h3>
      <div id="order-details-content" class="text-sm text-gray-700">
        Loading...
      </div>
      <div class="mt-4 text-right">
        <button onclick="closeOrderDetailsModal()" class="px-4 py-2 bg-pink-600 text-white rounded">Close</button>
      </div>
    </div>
  </div>

  <!-- Update Status Modal -->
  <div id="updateStatusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
    <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-lg">
      <h3 class="text-lg font-semibold mb-4">Update Order Status</h3>
      <form id="status-form">
        <input type="hidden" id="update-order-id" name="order_id">
        <select id="update-order-status" name="order_status" class="w-full border px-3 py-2 rounded mb-4">
          <option value="processing">Processing</option>
          <option value="shipped">Shipped</option>
        </select>
        <div class="text-right">
          <button type="button" onclick="submitStatusUpdate()"
            class="px-4 py-2 bg-pink-600 text-white rounded">Update</button>
          <button type="button" onclick="closeUpdateStatusModal()"
            class="ml-2 px-4 py-2 bg-gray-300 rounded">Cancel</button>
        </div>
      </form>
    </div>
  </div>


  <script>
    function showSection(id) {
      document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
      document.getElementById(id).classList.add('active');
      localStorage.setItem('adminSection', id); // save section
    }
    window.addEventListener('DOMContentLoaded', () => {
      const savedSection = localStorage.getItem('adminSection') || 'dashboard';
      showSection(savedSection);
    });

    function openEditModal(product) {
      document.getElementById('edit-id').value = product.id;
      document.getElementById('edit-name').value = product.name;
      document.getElementById('edit_description').value = product.description || ''; // new line
      document.getElementById('edit-return-category').value = new URLSearchParams(window.location.search).get('category') || '';
      document.getElementById('edit-category').value = product.category;
      document.getElementById('edit-price').value = product.price;
      document.getElementById('edit-stock').value = product.stock;
      document.getElementById('editModal').classList.remove('hidden');
      document.getElementById('editModal').classList.add('flex');
    }

    function closeEditModal() {
      document.getElementById('editModal').classList.remove('flex');
      document.getElementById('editModal').classList.add('hidden');
    }

    function openDeleteModal(id, name) {
      document.getElementById('delete-id').value = id;
      document.getElementById('delete-message').textContent = `Are you sure you want to delete "${name}"?`;
      document.getElementById('delete-return-category').value = new URLSearchParams(window.location.search).get('category') || '';
      document.getElementById('deleteModal').classList.remove('hidden');
      document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.remove('flex');
      document.getElementById('deleteModal').classList.add('hidden');
    }

    function openAddModal() {
      document.getElementById('addModal').classList.remove('hidden');
      document.getElementById('addModal').classList.add('flex');
    }

    function closeAddModal() {
      document.getElementById('addModal').classList.remove('flex');
      document.getElementById('addModal').classList.add('hidden');
    }

    function openOrderDetailsModal(orderId) {
      document.getElementById('viewOrderModal').classList.remove('hidden');
      fetch('fetch_order_details.php?id=' + orderId)
        .then(res => res.text())
        .then(html => {
          document.getElementById('order-details-content').innerHTML = html;
        })
        .catch(() => {
          document.getElementById('order-details-content').innerHTML = "Failed to load order details.";
        });
    }

    function closeOrderDetailsModal() {
      document.getElementById('viewOrderModal').classList.add('hidden');
    }

    function openUpdateStatusModal(orderId, currentStatus) {
      document.getElementById('update-order-id').value = orderId;
      document.getElementById('update-order-status').value = currentStatus;
      document.getElementById('updateStatusModal').classList.remove('hidden');
    }

    function closeUpdateStatusModal() {
      document.getElementById('updateStatusModal').classList.add('hidden');
    }

    function submitStatusUpdate() {
      const orderId = document.getElementById('update-order-id').value;
      const newStatus = document.getElementById('update-order-status').value;

      fetch('update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&order_status=${newStatus}`
      })
        .then(res => res.text())
        .then(response => {
          if (response === 'success') {
            location.reload();
          } else {
            alert("❌ Failed to update status.");
          }
        })
        .catch(() => alert("An error occurred while updating."));
    }

    setTimeout(() => {
      document.querySelectorAll('.bg-green-100, .bg-red-100').forEach(el => el.remove());
    }, 4000);

    function openDeliveryModal() {
      document.getElementById('deliveryModal').classList.remove('hidden');
    }

    function closeDeliveryModal() {
      document.getElementById('deliveryModal').classList.add('hidden');
    }
    setTimeout(() => {
      const msg = document.getElementById('successMessage');
      if (msg) {
        msg.classList.add('opacity-0');
        setTimeout(() => msg.remove(), 500);
      }
    }, 3500);
  </script>
</body>

</html>