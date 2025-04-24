<?php
session_start();
$conn = new mysqli("localhost", "root", "", "giftstore");

// Pagination setup
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

// Handle filters
$filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? '';
$order = 'ORDER BY name ASC';

if ($sort === 'price_asc') {
  $order = 'ORDER BY price ASC';
} elseif ($sort === 'price_desc') {
  $order = 'ORDER BY price DESC';
}

// Build condition string
$conditions = [];
$params = [];
$types = '';

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
  $types .= 'ii';
  $params[] = $limit;
  $params[] = $offset;
  $stmt->bind_param($types, ...$params);
} else {
  $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$products = $stmt->get_result();

// Fetch category list
$categories = $conn->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
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
  <div class="flex items-center w-full sm:w-[30rem] bg-white border-2 border-[#56c8d8] rounded-full shadow px-4 h-12 focus-within:ring-2 focus-within:ring-[#c0392b] transition">
    <input
      type="text"
      name="search"
      value="<?= htmlspecialchars($search) ?>"
      placeholder="Search products..."
      class="flex-1 bg-transparent border-none outline-none appearance-none text-gray-700 placeholder:text-gray-400 text-sm h-full leading-[3rem]"
    />
    <button
      type="submit"
      class="ml-2 bg-[#c0392b] hover:bg-red-700 text-white text-sm font-semibold px-5 h-9 rounded-full transition"
    >
      Search
    </button>
  </div>
</form>




    <!-- Product Grid -->
    <?php if ($products->num_rows > 0): ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php while ($prod = $products->fetch_assoc()): ?>
          <div
            class="bg-white p-6 rounded-2xl shadow-md hover:shadow-xl transition-transform transform hover:-translate-y-1">
            <img src="uploads/<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>"
              class="w-full h-60 object-cover rounded-xl mb-4 shadow-sm">
            <h3 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($prod['name']) ?></h3>
            <p class="text-base text-gray-500 mb-2"><?= htmlspecialchars($prod['category']) ?></p>
            <p class="text-yellow-600 font-bold mb-2">€<?= number_format($prod['price'], 2) ?></p>

            <?php
            $stock = $prod['stock'];
            $stock_class = $stock == 0 ? 'bg-red-500' : ($stock < 5 ? 'bg-yellow-400' : 'bg-green-500');
            $stock_text = $stock == 0 ? 'Out of Stock' : ($stock < 5 ? 'Low Stock' : 'In Stock');
            ?>
            <div class="mb-2">
              <span class="text-white text-xs px-2 py-1 rounded-full <?= $stock_class ?>">
                <?= $stock_text ?>
              </span>
            </div>


            <button onclick='openProductModal({
    id: <?= $prod["id"] ?>,
    name: "<?= htmlspecialchars($prod["name"]) ?>",
    category: "<?= htmlspecialchars($prod["category"]) ?>",
    price: <?= $prod["price"] ?>,
    stock: <?= $prod["stock"] ?>,
    image: "<?= htmlspecialchars($prod["image"]) ?>",
    description: "<?= htmlspecialchars($prod["description"] ?? '') ?>"
  })' class="w-full mb-2 bg-gray-100 hover:bg-gray-200 text-sm py-2 rounded">View Details</button>
            <form method="post" action="shopping_cart.php?action=add&id=<?= $prod["id"]; ?>">
              <input type="hidden" name="hidden_name" value="<?= htmlspecialchars($prod["name"]) ?>" />
              <input type="hidden" name="hidden_price" value="<?= $prod["price"] ?>" />
              <input type="hidden" name="hidden_img_id" value="<?= $prod["image"] ?>" />
              <input type="hidden" name="hidden_category" value="<?= $prod["category"] ?>" />

              <input type="number" name="item_quantity" value="1" min="1" max="<?= $prod['stock'] ?>"
                oninput="checkMaxQuantity(this, <?= $prod['stock'] ?>)"
                class="w-20 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none mb-2" />
              <small class="text-red-500 text-xs hidden" id="warning-main-<?= $prod['id'] ?>">You've reached the maximum
                quantity available.</small>

              <button type="submit" name="add_to_cart"
                class="w-full bg-emerald-600 text-white py-2 rounded hover:bg-emerald-700 <?= $stock == 0 ? 'opacity-50 cursor-not-allowed' : '' ?>"
                <?= $stock == 0 ? 'disabled' : '' ?>>
                Add to Cart
              </button>
            </form>

          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="text-center text-gray-500 mt-10">No products match your criteria.</p>
    <?php endif; ?>

    <!-- Pagination -->
    <div class="mt-10 flex justify-center gap-2">
      <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
          class="px-4 py-2 rounded <?= $p == $page ? 'bg-emerald-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
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