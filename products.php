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

<body class="bg-gray-100">
  <?php include("navbar.php"); ?>

  <div class="mx-20 px-4 py-20">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">🛍️ Explore Products</h2>

    <form method="GET" class="flex items-center justify-center gap-4 mb-6">
      <div
        class="flex items-center w-full sm:w-[30rem] bg-white border-2 border-[#56c8d8] rounded-full shadow px-4 h-12 focus-within:ring-2 focus-within:ring-[#c0392b] transition">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products..."
          class="flex-1 bg-transparent border-none outline-none appearance-none text-gray-700 placeholder:text-gray-400 text-sm h-full leading-[3rem]" />
        <button type="submit"
          class="ml-2 bg-[#c0392b] hover:bg-red-700 text-white text-sm font-semibold px-5 h-9 rounded-full transition">
          Search
        </button>
      </div>
    </form>





    <?php
    $current_gift = $_GET['gift'] ?? '';
    ?>

    <div class="flex flex-wrap justify-center gap-2 my-8">
      <a href="products.php"
        class="px-4 py-2 rounded-full border <?= $current_gift == '' ? 'bg-emerald-600 text-white' : 'text-gray-700 border-gray-300 hover:bg-gray-100' ?>">
        All Gifts
      </a>
      <?php foreach ($gift_categories as $cat): ?>
        <a href="products.php?gift=<?= urlencode($cat) ?>"
          class="px-4 py-2 rounded-full border <?= $current_gift == $cat ? 'bg-emerald-600 text-white' : 'text-gray-700 border-gray-300 hover:bg-gray-100' ?>">
          <?= htmlspecialchars($cat) ?>
        </a>
      <?php endforeach; ?>
    </div>


    <?php
    $selected_categories = $gift_filter !== '' ? [$gift_filter] : $gift_categories;
    foreach ($selected_categories as $gift_category):
      ?>
      <section class="mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($gift_category) ?></h2>
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
              <div class="bg-white rounded-xl shadow p-4 h-52 flex justify-between items-center">
                <!-- Text Section -->
                <div class="flex-1 pr-4">
                  <h3 class="text-base font-semibold text-gray-800 mb-1"><?= htmlspecialchars($prod['name'] ?? '') ?></h3>
                  <p class="text-xs text-gray-500 mb-1">
                    <?= !empty($prod['description']) ? htmlspecialchars($prod['description']) : 'No description available' ?>
                  </p>
                  <p class="text-sm text-gray-800 font-semibold mb-3">$<?= number_format($prod['price'], 2) ?></p>
                  <form method="post" action="shopping_cart.php?action=add&id=<?= $prod['id']; ?>">
                    <input type="hidden" name="hidden_name" value="<?= htmlspecialchars($prod['name']) ?>">
                    <input type="hidden" name="hidden_price" value="<?= $prod['price'] ?>">
                    <input type="hidden" name="hidden_img_id" value="<?= htmlspecialchars($prod['image']) ?>">
                    <input type="hidden" name="item_quantity" value="1">
                    <button type="submit" name="add_to_cart"
                      class="px-3 py-1 border border-gray-800 text-xs text-gray-800 rounded-full hover:bg-gray-100 transition">
                      Add to cart
                    </button>
                  </form>
                </div>

                <!-- Image Section -->
                <div class="w-24 h-28 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
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
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
            class="px-4 py-2 rounded <?= $p == $page ? 'bg-emerald-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>"
            <?= $p == $page ? 'aria-current="page"' : '' ?>>
            <?= $p ?>
          </a>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php include("footer.php"); ?>
</body>

</html>


<!-- Modal backdrop and position fix -->
<div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"
  onclick="closeProductModal(event)">
  <!-- Modal box -->
  <div
    class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl mx-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Close button -->
    <button onclick="closeProductModal()"
      class="absolute top-4 right-4 text-gray-500 hover:text-red-500 text-xl font-bold">&times;</button>

    <!-- Left: Image -->
    <img id="modal-image" src="" alt="Product Image" class="w-full h-72 object-cover rounded-lg">

    <!-- Right: Content -->
    <div class="flex flex-col justify-between">
      <div>
        <h2 id="modal-name" class="text-2xl font-bold text-gray-800 mb-2"></h2>
        <p id="modal-category" class="text-sm text-gray-500 mb-1"></p>
        <p id="modal-price" class="text-lg font-semibold text-emerald-600 mb-2"></p>
        <div id="modal-stock" class="mb-4"></div>
        <p id="modal-description" class="text-sm text-gray-700"></p>
      </div>

      <form method="post" action="shopping_cart.php?action=add&id=" id="modal-form">
        <input type="hidden" name="hidden_name" id="modal-hidden-name">
        <input type="hidden" name="hidden_price" id="modal-hidden-price">
        <input type="hidden" name="hidden_img_id" id="modal-hidden-img">
        <input type="hidden" name="hidden_category" id="modal-hidden-category">

        <input type="number" name="item_quantity" id="modal-quantity" min="1" value="1"
          class="w-24 px-3 py-2 border rounded-lg mb-2 shadow-sm" oninput="checkMaxQuantity(this, modalCurrentStock)">
        <small class="text-red-500 text-xs hidden" id="warning-modal">You've reached the maximum quantity
          available.</small>
        <button type="submit" name="add_to_cart" id="modal-submit-btn"
          class="w-full bg-emerald-600 text-white py-2 rounded hover:bg-emerald-700">
          Add to Cart
        </button>
      </form>
    </div>
  </div>
</div>


<script>
  let modalCurrentStock = 0;
  function openProductModal(data) {
    modalCurrentStock = parseInt(data.stock);
    document.getElementById('productModal').classList.remove('hidden');

    document.getElementById('modal-image').src = 'uploads/' + data.image;
    document.getElementById('modal-name').textContent = data.name;
    document.getElementById('modal-category').textContent = "Category: " + data.category;
    document.getElementById('modal-price').textContent = "€" + parseFloat(data.price).toFixed(2);
    document.getElementById('modal-description').textContent = data.description || "No description available.";

    // Set hidden form values
    document.getElementById('modal-hidden-name').value = data.name;
    document.getElementById('modal-hidden-price').value = data.price;
    document.getElementById('modal-hidden-img').value = data.image;
    document.getElementById('modal-hidden-category').value = data.category;

    // Set quantity input max to stock and reset quantity
    document.getElementById('modal-quantity').value = 1;
    document.getElementById('modal-quantity').max = data.stock;

    // Set form action dynamically with ID
    document.getElementById('modal-form').action = 'shopping_cart.php?action=add&id=' + data.id;

    // Handle stock status
    const stock = parseInt(data.stock);
    const btn = document.getElementById('modal-submit-btn');
    const stockBadge = document.getElementById('modal-stock');
    if (stock === 0) {
      btn.disabled = true;
      btn.classList.add('opacity-50', 'cursor-not-allowed');
      stockBadge.innerHTML = '<span class="text-xs text-white bg-red-500 px-2 py-1 rounded">Out of Stock</span>';
    } else {
      btn.disabled = false;
      btn.classList.remove('opacity-50', 'cursor-not-allowed');
      const badgeColor = stock < 5 ? 'bg-yellow-400' : 'bg-green-500';
      const badgeLabel = stock < 5 ? 'Low Stock' : 'In Stock';
      stockBadge.innerHTML = `<span class="text-xs text-white ${badgeColor} px-2 py-1 rounded">${badgeLabel}</span>`;
    }
  }

  function closeProductModal(e) {
    // Only close if the background is clicked (not inner modal content)
    if (!e || e.target.id === "productModal") {
      document.getElementById('productModal').classList.add('hidden');
    }
  }

  function checkMaxQuantity(input, maxQty) {
    const warningId = input.id.includes('modal') ? 'warning-modal' : `warning-main-${input.form.action.split('id=')[1]}`;
    const warningEl = document.getElementById(warningId);

    if (parseInt(input.value) >= maxQty) {
      if (warningEl) warningEl.classList.remove('hidden');
      input.value = maxQty;
    } else {
      if (warningEl) warningEl.classList.add('hidden');
    }
  }

</script>