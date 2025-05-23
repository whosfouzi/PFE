<?php
session_start();
$conn = new mysqli("localhost", "root", "", "giftstore");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current logged-in user ID, if any
$current_user_id = $_SESSION['id'] ?? null;
$user_liked_products = [];

// If user is logged in, fetch their liked products
if ($current_user_id) {
    $liked_stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    if ($liked_stmt) {
        $liked_stmt->bind_param("i", $current_user_id);
        $liked_stmt->execute();
        $liked_result = $liked_stmt->get_result();
        while ($row = $liked_result->fetch_assoc()) {
            $user_liked_products[] = $row['product_id'];
        }
        $liked_stmt->close();
    } else {
        // Handle error if needed, e.g., log $conn->error
    }
}


// Pagination setup
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 8; // Default limit for general view, might be overridden for carousel

$types = '';
// Handle filters
$filter = $_GET['category'] ?? ''; // Product category (e.g., electronics, books)
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? '';
$allowed_sorts = [
  'name_asc' => 'ORDER BY p.name ASC',
  'name_desc' => 'ORDER BY p.name DESC',
  'price_asc' => 'ORDER BY p.price ASC',
  'price_desc' => 'ORDER BY p.price DESC',
  'created_at_desc' => 'ORDER BY p.created_at DESC', // Newest
  'popularity' => 'ORDER BY p.view_count DESC' // Most popular (assuming view_count column)
];

$order = $allowed_sorts[$sort] ?? 'ORDER BY p.name ASC'; // Default sort
$base_query_select = "SELECT p.* FROM products p "; // Alias products table as 'p'
$conditions = [];
$params = [];
$types = '';

$gift_filter = $_GET['gift'] ?? ''; // Gift category (e.g., For Him, For Her)
if ($gift_filter !== '') {
  $conditions[] = 'p.gift_category = ?';
  $params[] = $gift_filter;
  $types .= 's';
}

if ($filter !== '') {
  $conditions[] = 'p.category = ?'; // Assuming 'category' is the column name in products table
  $params[] = $filter;
  $types .= 's';
}
if ($search !== '') {
  $conditions[] = 'p.name LIKE ?';
  $params[] = '%' . $search . '%';
  $types .= 's';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Total count for pagination (only relevant for full grid view)
$total_rows_grid = 0;
if ($gift_filter) { // Only calculate for grid view if a gift filter is applied
    $count_sql_grid = "SELECT COUNT(*) FROM products p $where";
    $count_stmt_grid = $conn->prepare($count_sql_grid);
    if ($count_stmt_grid) {
        if (!empty($params)) {
            $count_stmt_grid->bind_param($types, ...$params);
        }
        $count_stmt_grid->execute();
        $count_stmt_grid->bind_result($total_rows_grid);
        $count_stmt_grid->fetch();
        $count_stmt_grid->close();
    }
}


// Fetch distinct gift categories (For Him, For Her, etc.)
$gift_cat_stmt = $conn->prepare("SELECT DISTINCT gift_category FROM products WHERE gift_category IS NOT NULL AND gift_category != '' ORDER BY gift_category ASC");
$gift_cat_stmt->execute();
$gift_cat_result = $gift_cat_stmt->get_result();
$gift_categories = [];
while ($row = $gift_cat_result->fetch_assoc()) {
  $gift_categories[] = $row['gift_category'];
}
$gift_cat_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Products - GiftStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
  <style>
    body {
        font-family: 'Poppins', sans-serif;
    }
    input[type="text"] {
      border: none !important;
      box-shadow: none !important; /* Tailwind focus:ring-0 focus:border-transparent */
    }
    .mySwiper {
        position: relative;
    }
    .product-card-image-container {
        position: relative; /* For positioning the like button */
    }
    .like-button {
        position: absolute;
        top: 0.5rem; /* 8px */
        right: 0.5rem; /* 8px */
        background-color: rgba(255, 255, 255, 0.8);
        border-radius: 50%;
        padding: 0.375rem; /* 6px */
        cursor: pointer;
        transition: transform 0.2s ease-in-out, color 0.2s ease-in-out;
        z-index: 10;
    }
    .like-button:hover {
        transform: scale(1.1);
    }
    .like-button svg {
        width: 1.25rem; /* 20px */
        height: 1.25rem; /* 20px */
    }
    .like-button .liked-heart {
        color: #ef4444; /* Tailwind red-500 */
        fill: #ef4444;
    }
    .like-button .unliked-heart {
        color: #6b7280; /* Tailwind gray-500 */
        stroke-width: 2;
    }
    .product-card {
        height: 13rem; /* Approx 208px, adjust as needed based on h-52 */
        /* Or use min-height if content varies more */
    }
    .swiper-slide, .product-card {
      box-sizing: border-box;
  }
  /* Custom line clamp for product names */
  .line-clamp-2 {
    overflow: hidden;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    white-space: normal; /* Allow wrapping */
  }
  </style>
</head>

<body class="bg-gray-50 text-gray-800">
  <?php include("navbar.php"); ?>

  <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
    <h1 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-8">🛍️ Explore Our Products</h1>
    <form method="GET" action="products.php" class="flex items-center justify-center gap-4 mb-10">
      <div
        class="flex items-center w-full max-w-xl bg-white border-2 border-[#56c8d8] rounded-full shadow-sm px-2 h-12 focus-within:ring-2 focus-within:ring-[#56c8d8]/50 transition">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products by name..."
          class="flex-1 bg-transparent border-none outline-none appearance-none text-gray-700 placeholder:text-gray-400 text-sm h-full leading-[3rem] px-4 focus:ring-0" />
        <button type="submit" class="ml-2 text-white bg-[#56c8d8] hover:bg-[#45b1c0] text-sm font-semibold px-6 h-9 rounded-full transition">
          Search
        </button>
      </div>
    </form>

    <?php $current_gift_filter = $_GET['gift'] ?? ''; ?>
    <div class="flex flex-wrap justify-center gap-2 my-8">
      <a href="products.php" class="px-4 py-2 rounded-full font-semibold transition text-sm sm:text-base <?= $current_gift_filter == ''
        ? 'bg-[#56c8d8] text-white shadow-md'
        : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">
        All Gifts
      </a>
      <?php foreach ($gift_categories as $cat): ?>
        <a href="products.php?gift=<?= urlencode($cat) ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="px-4 py-2 rounded-full font-semibold transition text-sm sm:text-base <?= $current_gift_filter == $cat
            ? 'bg-[#56c8d8] text-white shadow-md'
            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">
          <?= htmlspecialchars($cat) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php
    $display_categories = $gift_filter ? [$gift_filter] : $gift_categories;

    foreach ($display_categories as $current_display_gift_category):
    ?>
      <section class="mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b-2 border-[#56c8d8] inline-block pb-1">
          <?= htmlspecialchars($current_display_gift_category) ?>
        </h2>

        <?php if (!$gift_filter): // If NO specific gift filter is active, show carousel ?>
          <div class="swiper-container-wrapper relative">
            <div class="swiper mySwiper">
              <div class="swiper-wrapper pb-8">
                <?php
                $carousel_limit = 5; // Number of items in carousel, excluding "View All"
                $carousel_query_params = [$current_display_gift_category];
                $carousel_query_types = 's';
                
                $carousel_conditions_sql = ['p.gift_category = ?'];
                if ($search !== '') {
                    $carousel_conditions_sql[] = 'p.name LIKE ?';
                    $carousel_query_params[] = '%' . $search . '%';
                    $carousel_query_types .= 's';
                }
                // Add other general filters if needed for carousel, e.g. product category
                // if ($filter !== '') { ... }

                $carousel_where_sql = 'WHERE ' . implode(' AND ', $carousel_conditions_sql);
                $carousel_sql = $base_query_select . $carousel_where_sql . " " . ($sort ? $order : "ORDER BY p.price DESC") . " LIMIT ?"; // Default sort by newest for carousel
                $carousel_query_params[] = $carousel_limit;
                $carousel_query_types .= 'i';

                $carousel_stmt = $conn->prepare($carousel_sql);
                if ($carousel_stmt) {
                    $carousel_stmt->bind_param($carousel_query_types, ...$carousel_query_params);
                    $carousel_stmt->execute();
                    $carousel_products_result = $carousel_stmt->get_result();

                    if ($carousel_products_result->num_rows > 0):
                      while ($prod = $carousel_products_result->fetch_assoc()):
                        $is_liked_by_user = $current_user_id ? in_array($prod['id'], $user_liked_products) : false;
                ?>
                        <div class="swiper-slide">
                          <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-4 product-card flex flex-col sm:flex-row justify-between items-center hover:shadow-xl transition-shadow duration-300">
                            <div class="flex-1 pr-0 sm:pr-4 w-full sm:w-auto mb-3 sm:mb-0 text-center sm:text-left">
                              <h3 class="text-sm font-semibold text-gray-800 mb-1 line-clamp-2" title="<?= htmlspecialchars($prod['name'] ?? '') ?>"><?= htmlspecialchars($prod['name'] ?? '') ?></h3>
                              <p class="text-xs text-gray-500 mb-1 h-8 overflow-hidden">
                                <?= !empty($prod['description']) ? htmlspecialchars(substr($prod['description'], 0, 50)) . (strlen($prod['description']) > 50 ? '...' : '') : 'No description available' ?>
                              </p>
                              <p class="text-sm text-[#56c8d8] font-semibold mb-2">DA <?= number_format($prod['price'], 2) ?></p>
                              <?php
                              $stock = $prod['stock'];
                              $stock_label = '';
                              $stock_color = '';
                              if ($stock == 0) {
                                  $stock_label = 'Out of Stock (0 left)';
                                  $stock_color = 'bg-red-500 text-white';
                              } elseif ($stock >= 1 && $stock <= 3) {
                                  $stock_label = 'Limited Stock (' . $stock . ' left)';
                                  $stock_color = 'bg-yellow-400 text-yellow-800';
                              } else { // Stock >= 4
                                  $stock_label = 'In Stock (' . $stock . ' left)';
                                  $stock_color = 'bg-green-500 text-white';
                              }
                              ?>
                              <span class="inline-block text-xs font-medium <?= $stock_color ?> px-2 py-0.5 rounded-full mb-2"><?= $stock_label ?></span>
                              <div>
                                <button onclick='openProductModal(<?= json_encode($prod) ?>)' class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-md text-xs font-medium transition-colors">
                                  View Details
                                </button>
                              </div>
                            </div>
                            <div class="w-full sm:w-40 h-32 sm:h-full flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden product-card-image-container">
                              <img src="uploads/<?= htmlspecialchars($prod['image'] ?? 'placeholder.jpg') ?>"
                                   alt="<?= htmlspecialchars($prod['name'] ?? '') ?>" class="w-full h-full object-cover"
                                   onerror="this.onerror=null;this.src='https://placehold.co/300x300/cccccc/969696?text=No+Image';">
                               <button 
                                    class="like-button" 
                                    data-product-id="<?= $prod['id'] ?>" 
                                    onclick="handleLikeClick(this, <?= $prod['id'] ?>)"
                                    aria-label="Like this product"
                                    title="<?= $is_liked_by_user ? 'Unlike' : 'Like' ?>">
                                    <svg class="<?= $is_liked_by_user ? 'liked-heart' : 'unliked-heart' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </div>
                          </div>
                        </div>
                <?php
                      endwhile;
                    else:
                ?>
                      <p class="text-sm text-gray-500 col-span-full text-center py-8">No products found in this category for the carousel.</p>
                <?php
                    endif;
                    if ($carousel_stmt) $carousel_stmt->close();
                } else {
                     echo "<p class='text-red-500'>Error preparing carousel statement: " . $conn->error . "</p>";
                }
                ?>
                <div class="swiper-slide flex items-center justify-center bg-transparent">
                  <a href="products.php?gift=<?= urlencode($current_display_gift_category) ?><?= $search ? '&search='.urlencode($search) : '' ?>"
                    class="flex flex-col items-center justify-center w-full h-full bg-white rounded-xl shadow-lg border border-gray-200 hover:bg-gray-50 transition p-4 product-card">
                    <span class="text-lg font-semibold text-[#56c8d8]">View All</span>
                    <span class="text-sm text-gray-600"><?= htmlspecialchars($current_display_gift_category) ?></span>
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-[#56c8d8] mt-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                  </a>
                </div>
              </div>
              <div class="swiper-pagination"></div>
              <div class="swiper-button-next text-[#56c8d8] after:text-2xl"></div>
              <div class="swiper-button-prev text-[#56c8d8] after:text-2xl"></div>
            </div>
          </div>

        <?php else: // Category filter IS active: Show full grid with pagination ?>
          <?php
            // Recalculate total rows for this specific filtered grid view
            $grid_page_limit = 9; // Products per page for grid view
            $grid_total_pages = max(1, ceil($total_rows_grid / $grid_page_limit));
            $current_page_grid = max(1, intval($_GET['page'] ?? 1));
            if ($current_page_grid > $grid_total_pages) $current_page_grid = $grid_total_pages;
            $grid_offset = ($current_page_grid - 1) * $grid_page_limit;

            $grid_sql = $base_query_select . $where . " " . $order . " LIMIT ? OFFSET ?";
            $grid_stmt = $conn->prepare($grid_sql);

            if ($grid_stmt) {
                $grid_final_types = $types . 'ii';
                $grid_final_params = array_merge($params, [$grid_page_limit, $grid_offset]);
                $grid_stmt->bind_param($grid_final_types, ...$grid_final_params);
                $grid_stmt->execute();
                $grid_products_result = $grid_stmt->get_result();
            } else {
                echo "<p class='text-red-500'>Error preparing grid statement: " . $conn->error . "</p>";
                $grid_products_result = null; // Ensure it's defined
            }
          ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2 gap-6">
            <?php
            if ($grid_products_result && $grid_products_result->num_rows > 0):
              while ($prod = $grid_products_result->fetch_assoc()):
                $is_liked_by_user = $current_user_id ? in_array($prod['id'], $user_liked_products) : false;
            ?>
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-4 product-card flex flex-col sm:flex-row justify-between items-center hover:shadow-xl transition-shadow duration-300">
                  <div class="flex-1 pr-0 sm:pr-4 w-full sm:w-auto mb-3 sm:mb-0 text-center sm:text-left">
                    <h3 class="text-sm font-semibold text-gray-800 mb-1 line-clamp-2" title="<?= htmlspecialchars($prod['name'] ?? '') ?>"><?= htmlspecialchars($prod['name'] ?? '') ?></h3>
                    <p class="text-xs text-gray-500 mb-1 h-8 overflow-hidden">
                         <?= !empty($prod['description']) ? htmlspecialchars(substr($prod['description'], 0, 50)) . (strlen($prod['description']) > 50 ? '...' : '') : 'No description available' ?>
                    </p>
                    <p class="text-sm text-[#56c8d8] font-semibold mb-2">DA <?= number_format($prod['price'], 2) ?></p>
                    <?php
                    $stock = $prod['stock'];
                    $stock_label = '';
                    $stock_color = '';
                    if ($stock == 0) {
                        $stock_label = 'Out of Stock (0 left)';
                        $stock_color = 'bg-red-500 text-white';
                    } elseif ($stock >= 1 && $stock <= 3) {
                        $stock_label = 'Limited Stock (' . $stock . ' left)';
                        $stock_color = 'bg-yellow-400 text-yellow-800';
                    } else { // Stock >= 4
                        $stock_label = 'In Stock (' . $stock . ' left)';
                        $stock_color = 'bg-green-500 text-white';
                    }
                    ?>
                    <span class="inline-block text-xs font-medium <?= $stock_color ?> px-2 py-0.5 rounded-full mb-2"><?= $stock_label ?></span>
                     <div>
                        <button onclick='openProductModal(<?= json_encode($prod) ?>)' class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-md text-xs font-medium transition-colors">
                          View Details
                        </button>
                    </div>
                  </div>
                  <div class="w-full sm:w-40 h-32 sm:h-full flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden product-card-image-container">
                    <img src="uploads/<?= htmlspecialchars($prod['image'] ?? 'placeholder.jpg') ?>"
                         alt="<?= htmlspecialchars($prod['name'] ?? '') ?>" class="w-full h-full object-cover"
                         onerror="this.onerror=null;this.src='https://placehold.co/300x300/cccccc/969696?text=No+Image';">
                    <button 
                        class="like-button" 
                        data-product-id="<?= $prod['id'] ?>" 
                        onclick="handleLikeClick(this, <?= $prod['id'] ?>)"
                        aria-label="Like this product"
                        title="<?= $is_liked_by_user ? 'Unlike' : 'Like' ?>">
                        <svg class="<?= $is_liked_by_user ? 'liked-heart' : 'unliked-heart' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </button>
                  </div>
                </div>
            <?php
              endwhile;
            else:
            ?>
              <p class="text-sm text-gray-500 col-span-full text-center py-10">No products found matching your criteria in this category.</p>
            <?php
            endif;
            if ($grid_stmt) $grid_stmt->close();
            ?>
          </div>

          <div class="mt-10 flex justify-center items-center gap-2">
            <?php if ($grid_total_pages > 1): ?>
                <?php if ($current_page_grid > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page_grid - 1])) ?>"
                       class="px-3 py-2 rounded-full font-semibold text-sm bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 transition">
                        &laquo; Prev
                    </a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $grid_total_pages; $p++): ?>
                    <?php if ($p == $current_page_grid): ?>
                        <span class="px-4 py-2 rounded-full font-semibold text-sm bg-[#56c8d8] text-white shadow-md" aria-current="page"><?= $p ?></span>
                    <?php elseif (abs($p - $current_page_grid) < 3 || $p == 1 || $p == $grid_total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
                           class="px-4 py-2 rounded-full font-semibold text-sm bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 transition">
                            <?= $p ?>
                        </a>
                    <?php elseif (abs($p - $current_page_grid) == 3 && ($p == 1 || $p == $grid_total_pages -1)): // Show ... ?>
                        <span class="px-4 py-2 text-gray-500">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page_grid < $grid_total_pages): ?>
                     <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page_grid + 1])) ?>"
                       class="px-3 py-2 rounded-full font-semibold text-sm bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 transition">
                        Next &raquo;
                    </a>
                <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endif; // End of grid view specific content ?>
      </section>
    <?php endforeach; // End of looping through display_categories ?>
     <?php if (empty($display_categories) && $gift_filter): // Case where a gift filter is applied but yields no category (e.g. bad URL param) ?>
        <p class="text-center text-gray-600 py-10">The selected gift category "<?= htmlspecialchars($gift_filter) ?>" does not exist or has no products.</p>
    <?php elseif (empty($gift_categories) && !$gift_filter): // Case where there are no gift categories at all ?>
        <p class="text-center text-gray-600 py-10">No products or gift categories are currently available. Please check back later!</p>
    <?php endif; ?>
  </div>

  <?php include("footer.php"); ?>

  <div id="productModal" class="fixed inset-0 bg-black bg-opacity-40 z-[100] hidden items-center justify-center p-4"
    onclick="closeProductModal(event, true)">
    <div class="bg-white w-full max-w-3xl mx-auto p-6 rounded-xl shadow-2xl flex flex-col md:flex-row gap-6 relative transform transition-all duration-300 ease-in-out opacity-0 scale-95"
      onclick="event.stopPropagation()">
      <button onclick="closeProductModal(event)"
        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-2xl z-10">&times;</button>
      <div class="w-full md:w-1/2 flex-shrink-0">
        <img id="modal-image" src="" alt="Product Image" class="w-full h-64 md:h-full object-cover rounded-lg shadow-md" 
             onerror="this.onerror=null;this.src='https://placehold.co/400x400/cccccc/969696?text=No+Image';" />
      </div>
      <div class="w-full md:w-1/2 flex flex-col justify-center">
        <h2 id="modal-name" class="text-2xl lg:text-3xl font-bold text-gray-800 mb-2"></h2>
        <p id="modal-category" class="text-sm text-gray-500 mb-3"></p>
        <p id="modal-price" class="text-xl lg:text-2xl font-semibold text-[#56c8d8] mb-4"></p>
        <div class="prose prose-sm max-w-none text-gray-700 mb-4 h-24 overflow-y-auto">
            <p id="modal-description"></p>
        </div>
        <div id="modal-stock" class="mb-4 text-sm"></div>
        <form id="modal-form" method="post" action="" class="flex flex-col gap-3 mt-auto">
          <input type="hidden" id="modal-hidden-id" name="hidden_id" />
          <input type="hidden" id="modal-hidden-name" name="hidden_name" />
          <input type="hidden" id="modal-hidden-price" name="hidden_price" />
          <input type="hidden" id="modal-hidden-img" name="hidden_img_id" />
          <input type="hidden" id="modal-hidden-category" name="hidden_category" />
          <div class="flex items-center gap-3">
            <label for="modal-quantity" class="text-sm font-medium text-gray-700">Quantity:</label>
            <input type="number" name="item_quantity" id="modal-quantity" min="1" value="1"
              class="w-20 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-1 focus:ring-[#56c8d8] focus:border-[#56c8d8]"
              oninput="checkMaxQuantity(this, modalCurrentStock)" />
          </div>
          <small class="text-red-500 text-xs hidden" id="warning-modal">
            You’ve reached the maximum quantity available.
          </small>
          <button type="submit" id="modal-submit-btn" name="add_to_cart"
            class="w-full bg-[#56c8d8] text-white py-2.5 rounded-lg hover:bg-[#45b1c0] transition-colors font-semibold text-sm">Add to Cart</button>
        </form>
      </div>
    </div>
  </div>

  <div id="likeToast" class="fixed bottom-5 right-5 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-md text-sm z-[150] opacity-0 transition-opacity duration-300 ease-in-out">
        Product liked!
    </div>
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script>
    // For Product Modal
    let modalCurrentStock = 0;

    function openProductModal(productData) {
        const data = typeof productData === 'string' ? JSON.parse(productData) : productData; // Handle both string and object
        modalCurrentStock = parseInt(data.stock);
        const modal = document.getElementById('productModal');
        const modalContent = modal.querySelector(':scope > div');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => { // For animation
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('opacity-0', 'scale-95');
        }, 10);

        document.getElementById('modal-form').action = 'shopping_cart.php?action=add&id=' + data.id;
        document.getElementById('modal-hidden-id').value = data.id;
        document.getElementById('modal-image').src = 'uploads/' + (data.image || 'placeholder.jpg');
        document.getElementById('modal-name').textContent = data.name;
        document.getElementById('modal-category').textContent = "Category: " + data.category;
        document.getElementById('modal-price').textContent = "DA " + parseFloat(data.price).toFixed(2);
        document.getElementById('modal-description').textContent = data.description || "No description available.";
        document.getElementById('modal-hidden-name').value = data.name;
        document.getElementById('modal-hidden-price').value = data.price;
        document.getElementById('modal-hidden-img').value = data.image; // This is image filename
        document.getElementById('modal-hidden-category').value = data.category;

        const qtyInput = document.getElementById('modal-quantity');
        qtyInput.value = 1;
        qtyInput.max = data.stock;

        const btn = document.getElementById('modal-submit-btn');
        const stockBadge = document.getElementById('modal-stock');
        const warning = document.getElementById('warning-modal');

        if (data.stock === 0) {
            btn.disabled = true;
            btn.textContent = 'Out of Stock';
            btn.classList.add('bg-gray-400', 'cursor-not-allowed');
            btn.classList.remove('bg-[#56c8d8]', 'hover:bg-[#45b1c0]');
            qtyInput.disabled = true;
            stockBadge.innerHTML = '<span class="text-xs text-white bg-red-500 px-2 py-1 rounded-full font-semibold">Out of Stock (0 left)</span>';
            warning.classList.add('hidden');
        } else {
            btn.disabled = false;
            btn.textContent = 'Add to Cart';
            btn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            btn.classList.add('bg-[#56c8d8]', 'hover:bg-[#45b1c0]');
            qtyInput.disabled = false;
            // Updated stock badge logic for modal
            let badgeColor, badgeLabel;
            if (data.stock >= 4) {
                badgeColor = 'bg-green-500 text-white';
                badgeLabel = `In Stock (${data.stock} left)`;
            } else if (data.stock >= 1 && data.stock <= 3) {
                badgeColor = 'bg-yellow-400 text-yellow-800';
                badgeLabel = `Limited Stock (${data.stock} left)`;
            } else { // Should already be caught by data.stock === 0, but as fallback
                badgeColor = 'bg-red-500 text-white';
                badgeLabel = 'Out of Stock (0 left)';
            }
            stockBadge.innerHTML = `<span class="text-xs ${badgeColor} px-2 py-1 rounded-full font-semibold">${badgeLabel}</span>`;
        }
    }

    function closeProductModal(e, fromOverlay = false) {
        const modal = document.getElementById('productModal');
        const modalContent = modal.querySelector(':scope > div');
        if (fromOverlay && e.target.id !== "productModal") {
            return;
        }
        modal.classList.add('opacity-0');
        modalContent.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 300); // Match transition duration
    }


    function checkMaxQuantity(input, maxQty) {
      const warningId = input.id.includes('modal') ? 'warning-modal' : `warning-main-${input.form.action.split('id=')[1]}`;
      const warningEl = document.getElementById(warningId);
      const currentVal = parseInt(input.value);

      if (currentVal > maxQty) {
        if (warningEl) {
          warningEl.textContent = `Only ${maxQty} left in stock.`;
          warningEl.classList.remove('hidden');
          setTimeout(() => { warningEl.classList.add('hidden'); }, 2500);
        }
        input.value = maxQty;
      } else if (currentVal < 1 && input.value !== '') {
         input.value = 1; // Prevent going below 1
      } else {
        if (warningEl) warningEl.classList.add('hidden');
      }
    }

    // Swiper Initialization
    const swipers = document.querySelectorAll(".mySwiper");
    swipers.forEach(function(swiperElement) {
        new Swiper(swiperElement, {
            slidesPerView: 1, // Default for very small screens
            spaceBetween: 20,
            loop: false, // Better for "View All" last slide
            pagination: {
                el: swiperElement.parentElement.querySelector(".swiper-pagination"), // Correctly target pagination
                clickable: true,
            },
            navigation: {
                nextEl: swiperElement.parentElement.querySelector(".swiper-button-next"),
                prevEl: swiperElement.parentElement.querySelector(".swiper-button-prev"),
            },
            breakpoints: {
                640: { slidesPerView: 2, spaceBetween: 20 }, // sm: show 2 full items
                768: { slidesPerView: 2, spaceBetween: 30 }, // md: show 2 full items
                1024: { slidesPerView: 2, spaceBetween: 30 }, // lg: show 2 full items
                1280: { slidesPerView: 2, spaceBetween: 40 } // xl: show 2 full items
            },
        });
    });

    // Like Button Functionality
    const loggedInUserId = <?= $current_user_id ? $current_user_id : 'null' ?>;
    const heartOutlineSVG = `<svg class="unliked-heart" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>`;
    const heartFilledSVG = `<svg class="liked-heart" fill="currentColor" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>`;
    const likeToast = document.getElementById('likeToast');
    let toastTimeout;

    function showLikeToast(message) {
        if (toastTimeout) clearTimeout(toastTimeout);
        likeToast.textContent = message;
        likeToast.classList.remove('opacity-0');
        toastTimeout = setTimeout(() => {
            likeToast.classList.add('opacity-0');
        }, 2000);
    }


    function handleLikeClick(buttonElement, productId) {
        if (!loggedInUserId) {
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            return;
        }

        // Prevent multiple rapid clicks
        if (buttonElement.classList.contains('processing-like')) {
            return;
        }
        buttonElement.classList.add('processing-like');


        const formData = new FormData();
        formData.append('product_id', productId);

        fetch('toggle_like.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const svgContainer = buttonElement; // The button itself is the SVG container now
                if (data.liked) {
                    svgContainer.innerHTML = heartFilledSVG;
                    svgContainer.title = 'Unlike';
                    showLikeToast('Added to Likes!');
                } else {
                    svgContainer.innerHTML = heartOutlineSVG;
                    svgContainer.title = 'Like';
                    showLikeToast('Removed from Likes');
                }
            } else {
                if (data.loggedIn === false) { // Check if server explicitly said user is not logged in
                     window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                } else {
                    console.error('Error toggling like:', data.message);
                    showLikeToast('Error: ' + (data.message || 'Could not update like.'));
                }
            }
        })
        .catch(error => {
            console.error('Network error toggling like:', error);
            showLikeToast('Network error. Please try again.');
        })
        .finally(() => {
             buttonElement.classList.remove('processing-like');
        });
    }
  </script>

</body>
</html>