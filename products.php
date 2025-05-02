<?php
session_start();
$conn = new mysqli("localhost", "root", "", "giftstore");

// Pagination setup
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;
$types = '';
// Handle filters
$filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? '';
$allowed_sorts = [
  'name_asc' => 'ORDER BY name ASC',
  'price_asc' => 'ORDER BY price ASC',
  'price_desc' => 'ORDER BY price DESC'
];
$order = $allowed_sorts[$sort] ?? 'ORDER BY name ASC';


if ($sort === 'price_asc') {
  $order = 'ORDER BY price ASC';
} elseif ($sort === 'price_desc') {
  $order = 'ORDER BY price DESC';
}

$conditions = [];
$params = [];
$types = '';
// Build condition string

$gift_filter = $_GET['gift'] ?? '';
if ($gift_filter !== '') {
  $conditions[] = 'gift_category = ?';
  $params[] = $gift_filter;
  $types .= 's';
}

if ($filter !== '') {
  $conditions[] = 'category = ?';
  $params[] = $filter;
  $types .= 's';
}
if ($search !== '') {
  $conditions[] = 'name LIKE ?';
  $params[] = '%' . $search . '%';
  $types .= 's';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Total count for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM products $where");
if ($params)
  $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_rows);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = ceil($total_rows / $limit);

// Fetch products
$sql = "SELECT * FROM products $where $order LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($params) {
  $final_types = $types . 'ii';
  $final_params = array_merge($params, [$limit, $offset]);
  $stmt->bind_param($final_types, ...$final_params);

} else {
  $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$products = $stmt->get_result();

// Fetch category list
$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
$categoryList = [];
if ($categories && $categories->num_rows > 0) {
  while ($row = $categories->fetch_assoc()) {
    $categoryList[] = $row['category'];
  }
}
// Fetch distinct gift categories
$cat_stmt = $conn->prepare("SELECT DISTINCT gift_category FROM products WHERE gift_category IS NOT NULL AND gift_category != ''");
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
$gift_categories = [];

while ($row = $cat_result->fetch_assoc()) {
  $gift_categories[] = $row['gift_category'];
}
$cat_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Products - GiftStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    input[type="text"] {
      border: none !important;
      box-shadow: none !important;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-800" class="bg-gray-50 text-gray-800">
  <?php include("navbar.php"); ?>

  <div class="mx-20 px-4 py-20">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">🛍️ Explore Products</h2>
    <!-- Search Bar -->
    <form method="GET" class="flex items-center justify-center gap-4 mb-6">
      <div
        class="flex items-center w-full sm:w-[30rem] bg-white border-2 border-[#56c8d8] rounded-full shadow px-4 h-12 focus-within:ring-2 focus-within:ring-[#c0392b] transition">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products..."
          class="flex-1 bg-transparent border-none outline-none appearance-none text-gray-700 placeholder:text-gray-400 text-sm h-full leading-[3rem]" />
        <button type="submit"
          class="ml-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 h-9 rounded-full transition">
          Search
        </button>
      </div>
    </form>

    <?php
    $current_gift = $_GET['gift'] ?? '';
    ?>

    <div class="flex flex-wrap justify-center gap-2 my-8">
      <a href="products.php" class="px-4 py-2 rounded-full font-semibold transition <?= $current_gift == ''
        ? 'bg-[#56c8d8] text-white shadow-sm'
        : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
        All Gifts
      </a>
      <?php foreach ($gift_categories as $cat): ?>
        <a href="products.php?gift=<?= urlencode($cat) ?>" class="px-4 py-2 rounded-full font-semibold transition <?= $current_gift == $cat
            ? 'bg-[#56c8d8] text-white shadow-sm'
            : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
          <?= htmlspecialchars($cat) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php
    $selected_categories = $gift_filter !== '' ? [$gift_filter] : $gift_categories;
    foreach ($selected_categories as $gift_category):
      ?>
      <section class="mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b-2 border-red-500 inline-block">
          <?= htmlspecialchars($gift_category) ?></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php
          $query = "SELECT * FROM products WHERE gift_category = ?";
          $local_conditions = $conditions;
          $local_params = $params;
          $local_types = $types;

          if (!empty($local_conditions)) {
            $query .= " AND (" . implode(" AND ", $local_conditions) . ")";
          }
          $query .= " $order LIMIT ? OFFSET ?";

          $stmt = $conn->prepare($query);
          $bind_types = 's' . $local_types . 'ii';
          $bind_values = array_merge([$gift_category], $local_params, [$limit, $offset]);
          $stmt->bind_param($bind_types, ...$bind_values);
          $stmt->execute();
          $products = $stmt->get_result();

          if ($products->num_rows > 0):
            while ($prod = $products->fetch_assoc()):
              ?>
              <div
                class="bg-white rounded-xl shadow-md border border-gray-100 p-4 h-52 flex justify-between items-center hover:shadow-lg transition">
                <!-- Text Section -->
                <div class="flex-1 pr-4">
                  <h3 class="text-base font-semibold text-gray-800 mb-1"><?= htmlspecialchars($prod['name'] ?? '') ?></h3>
                  <p class="text-xs text-gray-500 mb-1">
                    <?= !empty($prod['description']) ? htmlspecialchars($prod['description']) : 'No description available' ?>
                  </p>
                  <p class="text-sm text-gray-800 font-semibold mb-1">DA <?= number_format($prod['price'], 2) ?></p>

                  <?php
                  $stock = $prod['stock'];
                  if ($stock == 0) {
                    $stock_label = 'Out of stock';
                    $stock_color = 'bg-red-500';
                  } elseif ($stock < 4) {
                    $stock_label = 'Limited stock';
                    $stock_color = 'bg-yellow-400';
                  } else {
                    $stock_label = 'In stock';
                    $stock_color = 'bg-green-500';
                  }
                  ?>
                  <span
                    class="inline-block text-xs text-white <?= $stock_color ?> px-2 py-1 rounded mb-2"><?= $stock_label ?></span>

                  <button onclick='openProductModal({
                      id: <?= $prod["id"] ?>,
                      name: "<?= htmlspecialchars($prod["name"]) ?>",
                      category: "<?= htmlspecialchars($prod["category"]) ?>",
                      price: <?= $prod["price"] ?>,
                      stock: <?= $prod["stock"] ?>,
                      image: "<?= htmlspecialchars($prod["image"]) ?>",
                      description: "<?= htmlspecialchars($prod["description"] ?? 'No description available') ?>"
                    })' class="bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded text-sm">
                    View Details
                  </button>

                </div>

                <!-- Image Section -->
                <div class="w-56 h-44 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                  <img src="uploads/<?= htmlspecialchars($prod['image'] ?? '') ?>"
                    alt="<?= htmlspecialchars($prod['name'] ?? '') ?>" class="w-full h-full object-cover">
                </div>
              </div>
            <?php endwhile; else: ?>
            <p class="text-sm text-gray-500 col-span-full">No products in this category.</p>
          <?php endif;
          $stmt->close(); ?>
        </div>
      </section>
    <?php endforeach; ?>

    <!-- Pagination -->
    <div class="mt-10 flex justify-center gap-2">
      <?php if ($total_pages > 1): ?>
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="px-4 py-2 rounded-full font-semibold transition <?= $p == $page
                 ? 'bg-[#56c8d8] text-white shadow-sm'
                 : 'bg-white text-gray-600 hover:bg-gray-100' ?>" <?= $p == $page ? 'aria-current="page"' : '' ?>>
            <?= $p ?>
          </a>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php include("footer.php"); ?>

 <!-- Product Detail Modal -->
<div id="productModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden items-center justify-center"
  onclick="closeProductModal(event)">
  <div class="bg-white w-full max-w-3xl mx-auto p-6 rounded-lg shadow-lg flex gap-6 relative"
    onclick="event.stopPropagation()">

    <button onclick="closeProductModal()"
      class="absolute top-2 right-2 text-gray-500 hover:text-[#56c8d8] text-2xl transition">&times;</button>

    <!-- Left Side - Image fills entire side -->
    <div class="w-1/2 flex">
      <img id="modal-image" src="" alt="" class="w-full object-cover rounded h-full max-h-[500px]" />
    </div>

    <!-- Right Side - Content -->
    <div class="w-1/2 flex flex-col">
      <h2 id="modal-name" class="text-2xl font-bold text-gray-800 mb-1"></h2>
      <p id="modal-category" class="text-sm text-gray-500 mb-2"></p>
      <p id="modal-price" class="text-lg font-semibold text-[#56c8d8] mb-2"></p>
      <p id="modal-description" class="text-sm text-gray-700 mb-3"></p>
      <div id="modal-stock" class="mb-2"></div>
      <form id="modal-form" method="post" action="" class="flex flex-col gap-2 mt-auto">
        <input type="hidden" id="modal-hidden-name" name="hidden_name" />
        <input type="hidden" id="modal-hidden-price" name="hidden_price" />
        <input type="hidden" id="modal-hidden-img" name="hidden_img_id" />
        <input type="hidden" id="modal-hidden-category" name="hidden_category" />
        <input type="number" name="item_quantity" id="modal-quantity" min="1" value="1"
          class="w-24 px-3 py-2 border border-gray-300 rounded shadow-sm focus:outline-none"
          oninput="checkMaxQuantity(this, modalCurrentStock)" />
        <small class="text-red-500 text-xs hidden" id="warning-modal">
          You’ve reached the maximum quantity available.
        </small>
        <button type="submit" id="modal-submit-btn" name="add_to_cart"
          class="w-full bg-[#56c8d8] text-white py-2 rounded hover:bg-[#49b7c5] transition">Add to Cart</button>
      </form>
    </div>
  </div>
</div>


  <script>
    let modalCurrentStock = 0;
    function openProductModal(data) {
      modalCurrentStock = parseInt(data.stock);
      document.getElementById('productModal').classList.remove('hidden');
      document.getElementById('productModal').classList.add('flex');
      document.getElementById('modal-form').action = 'shopping_cart.php?action=add&id=' + data.id;

      document.getElementById('modal-image').src = 'uploads/' + data.image;
      document.getElementById('modal-name').textContent = data.name;
      document.getElementById('modal-category').textContent = "Category: " + data.category;
      document.getElementById('modal-price').textContent = "DA " + parseFloat(data.price).toFixed(2);
      document.getElementById('modal-description').textContent = data.description || "No description available.";

      document.getElementById('modal-hidden-name').value = data.name;
      document.getElementById('modal-hidden-price').value = data.price;
      document.getElementById('modal-hidden-img').value = data.image;
      document.getElementById('modal-hidden-category').value = data.category;

      const qtyInput = document.getElementById('modal-quantity');
      qtyInput.value = 1;
      qtyInput.max = data.stock;

      const btn = document.getElementById('modal-submit-btn');
      const stockBadge = document.getElementById('modal-stock');
      const warning = document.getElementById('warning-modal');

      if (data.stock === 0) {
        btn.disabled = true;
        qtyInput.disabled = true;
        stockBadge.innerHTML = '<span class="text-xs text-white bg-red-500 px-2 py-1 rounded">Out of Stock</span>';
        warning.classList.add('hidden');
      } else {
        btn.disabled = false;
        qtyInput.disabled = false;
        const badgeColor = data.stock < 5 ? 'bg-yellow-400' : 'bg-green-500';
        const badgeLabel = data.stock < 5 ? 'Low Stock' : 'In Stock';
        stockBadge.innerHTML = `<span class="text-xs text-white ${badgeColor} px-2 py-1 rounded">${badgeLabel}</span>`;
      }
    }

    function closeProductModal(e) {
      if (!e || e.target.id === "productModal") {
        document.getElementById('productModal').classList.remove('flex');
        document.getElementById('productModal').classList.add('hidden');
      }
    }

    function checkMaxQuantity(input, maxQty) {
      const warningId = input.id.includes('modal') ? 'warning-modal' : `warning-main-${input.form.action.split('id=')[1]}`;
      const warningEl = document.getElementById(warningId);

      if (parseInt(input.value) >= maxQty) {
        if (warningEl) {
          warningEl.classList.remove('hidden');

          // Hide it again after 2.5 seconds
          setTimeout(() => {
            warningEl.classList.add('hidden');
          }, 2500);
        }

        input.value = maxQty; // Clamp value
      } else {
        if (warningEl) warningEl.classList.add('hidden');
      }
    }
  </script>

</body>

</html>