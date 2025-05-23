<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    error_log("Database Connection failed in admin.php: " . $db->connect_error);
    die("Database connection failed. Please try again later.");
}
$db->set_charset("utf8mb4");

// Fetch dashboard metrics
$total_users_query = $db->query("SELECT COUNT(*) as total FROM users");
$total_users = $total_users_query->fetch_assoc()['total'];

$total_products_query = $db->query("SELECT COUNT(*) as total FROM products");
$total_products = $total_products_query->fetch_assoc()['total'];

$total_orders_query = $db->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $total_orders_query->fetch_assoc()['total'];

$total_revenue_query = $db->query("SELECT SUM(total_price) as total FROM orders WHERE order_status = 'completed'");
$total_revenue = $total_revenue_query->fetch_assoc()['total'] ?? 0;

$pending_orders_query = $db->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'processing'"); // Assuming 'processing' is pending
$pending_orders = $pending_orders_query->fetch_assoc()['total'];

$out_of_stock_query = $db->query("SELECT COUNT(*) as total FROM products WHERE stock = 0");
$out_of_stock_products = $out_of_stock_query->fetch_assoc()['total'];


$users = $db->query("SELECT id, fname, lname, username, email, role FROM users ORDER BY role, id");

// Fetch current admin's details for "Edit Profile" section
$admin_id = $_SESSION['id'];
$admin_details = [];
$stmt_admin = $db->prepare("SELECT username, email, fname, lname, phone, created_at FROM users WHERE id = ? AND role = 'admin'");
if ($stmt_admin) {
    $stmt_admin->bind_param("i", $admin_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    $admin_details = $result_admin->fetch_assoc();
    $stmt_admin->close();
} else {
    error_log("Admin details fetch prepare failed: " . $db->error);
}


// Handle products sorting and filtering
$validSorts = ['price', 'stock', 'name', 'category']; // Added name and category for sorting
$sort = in_array($_GET['sort'] ?? '', $validSorts) ? $_GET['sort'] : 'id';
$order = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

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

$count_sql = "SELECT COUNT(*) FROM products $where";
$count_stmt = $db->prepare($count_sql);
if (!empty($params)) {
  $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_stmt->bind_result($total_products_paginated); // Renamed to avoid conflict with dashboard metric
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_products_paginated / $limit);

// IMPORTANT: Added 'description' to the SELECT query for products
$sql = "SELECT id, name, category, price, stock, image, description FROM products $where ORDER BY $sort $order LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
if (!empty($params)) {
  $types_with_pagination = $types . 'ii';
  $params_with_pagination = array_merge($params, [$limit, $offset]);
  $stmt->bind_param($types_with_pagination, ...$params_with_pagination);
} else {
  $stmt->bind_param("ii", $limit, $offset);
}

$category_counts = [];
$result_cat_counts = $db->query("SELECT category, COUNT(*) as total FROM products GROUP BY category");
while ($row = $result_cat_counts->fetch_assoc()) {
  $category_counts[$row['category']] = (int) $row['total'];
}

$stmt->execute();
$products = $stmt->get_result();

// Fetch distinct categories for the dropdown
$all_categories_query = $db->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
$all_categories = [];
while ($cat_row = $all_categories_query->fetch_assoc()) {
    $all_categories[] = $cat_row['category'];
}

// Define gift categories for the dropdown
$gift_categories_list = [
    'Gifts for Him',
    'Gifts for Her',
    'Gifts for Kids',
    'Tech Gifts',
    'Home & Decor',
    'For Birthdays',
    'Anniversary Gifts',
    'Wedding Gifts',
    'Personalized Gifts',
    'Seasonal Gifts',
    'General' // A fallback or general category
];


// Fetch orders (pagination + item count + status)
$order_page = max(1, intval($_GET['order_page'] ?? 1));
$order_limit = 10;
$order_offset = ($order_page - 1) * $order_limit;
$order_sort = ($_GET['order_sort'] ?? 'created_at');
$order_dir = ($_GET['order_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$order_total_query = $db->query("SELECT COUNT(*) as total FROM orders WHERE order_status NOT IN ('completed', 'returned')"); // Exclude archived orders
$order_total = $order_total_query->fetch_assoc()['total'];
$order_total_pages = ceil($order_total / $order_limit);

$order_sql = "
  SELECT o.id, o.user_name, o.email, o.total_price, o.order_status, o.created_at, COUNT(oi.id) as item_count
  FROM orders o
  LEFT JOIN order_items oi ON o.id = oi.order_id
  WHERE o.order_status NOT IN ('completed', 'returned')
  GROUP BY o.id
  ORDER BY o.$order_sort $order_dir
  LIMIT $order_limit OFFSET $order_offset
";
$orders = $db->query($order_sql);

// Fetch archived orders (completed and returned)
$archive_page = max(1, intval($_GET['archive_page'] ?? 1));
$archive_limit = 10;
$archive_offset = ($archive_page - 1) * $archive_limit;
$archive_sort = ($_GET['archive_sort'] ?? 'created_at');
$archive_dir = ($_GET['archive_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$archive_total_query = $db->query("SELECT COUNT(*) as total FROM orders WHERE order_status IN ('completed', 'returned')");
$archive_total = $archive_total_query->fetch_assoc()['total'];
$archive_total_pages = ceil($archive_total / $archive_limit);

$archive_sql = "
  SELECT o.id, o.user_name, o.email, o.total_price, o.order_status, o.created_at, COUNT(oi.id) as item_count
  FROM orders o
  LEFT JOIN order_items oi ON o.id = oi.order_id
  WHERE o.order_status IN ('completed', 'returned')
  GROUP BY o.id
  ORDER BY o.$archive_sort $archive_dir
  LIMIT $archive_limit OFFSET $archive_offset
";
$archived_orders = $db->query($archive_sql);


// Fetch reviews (with new star filter)
$reviews_page = max(1, intval($_GET['reviews_page'] ?? 1));
$reviews_limit = 10;
$reviews_offset = ($reviews_page - 1) * $reviews_limit;
$reviews_sort = ($_GET['reviews_sort'] ?? 'created_at');
$reviews_dir = ($_GET['reviews_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$filter_stars = isset($_GET['filter_stars']) ? intval($_GET['filter_stars']) : 0; // New filter parameter

$reviews_conditions = [];
$reviews_params = [];
$reviews_types = '';

if ($filter_stars > 0 && $filter_stars <= 5) {
    $reviews_conditions[] = "r.rating = ?";
    $reviews_params[] = $filter_stars;
    $reviews_types .= 'i';
}

$reviews_where = $reviews_conditions ? "WHERE " . implode(" AND ", $reviews_conditions) : "";

$reviews_total_query_sql = "SELECT COUNT(*) as total FROM reviews r $reviews_where";
$reviews_total_query = $db->prepare($reviews_total_query_sql);
if (!empty($reviews_params)) {
    $reviews_total_query->bind_param($reviews_types, ...$reviews_params);
}
$reviews_total_query->execute();
$reviews_total_query->bind_result($reviews_total);
$reviews_total_query->fetch();
$reviews_total_query->close();

$reviews_total_pages = ceil($reviews_total / $reviews_limit);

$reviews_sql = "
    SELECT r.id, r.rating, r.comment, r.created_at, r.is_featured, u.username, o.id as order_id
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN orders o ON r.order_id = o.id
    $reviews_where
    ORDER BY r.$reviews_sort $reviews_dir
    LIMIT ? OFFSET ?
";
$stmt_reviews = $db->prepare($reviews_sql);

if (!empty($reviews_params)) {
    $reviews_types_with_pagination = $reviews_types . 'ii';
    $reviews_params_with_pagination = array_merge($reviews_params, [$reviews_limit, $reviews_offset]);
    $stmt_reviews->bind_param($reviews_types_with_pagination, ...$reviews_params_with_pagination);
} else {
    $stmt_reviews->bind_param("ii", $reviews_limit, $reviews_offset);
}
$stmt_reviews->execute();
$reviews = $stmt_reviews->get_result();


$db->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GiftStore Admin - Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Pacifico&family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --admin-primary: #56c8d8; /* Turquoise */
      --admin-primary-dark: #45b1c0;
      --admin-accent: #ef4444; /* Red */
      --admin-accent-dark: #dc2626;
      --sidebar-bg-start: #2d3748; /* Darker gray */
      --sidebar-bg-end: #1a202c; /* Even darker gray */
      --text-light: #e2e8f0;
      --text-dark: #2d3748;
      --card-bg-start: #ffffff;
      --card-bg-end: #f0f9ff;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f7fa 100%);
      color: var(--text-dark);
      min-height: 100vh;
      display: flex;
    }

    .pacifico-font { font-family: 'Pacifico', cursive; }
    .dancing-script-font { font-family: 'Dancing Script', cursive; }

    /* Custom Admin Colors */
    .admin-primary { background-color: var(--admin-primary); }
    .admin-primary-dark { background-color: var(--admin-primary-dark); }
    .admin-accent { background-color: var(--admin-accent); }
    .admin-accent-dark { background-color: var(--admin-accent-dark); }
    .text-admin-primary { color: var(--admin-primary); }
    .text-admin-accent { color: var(--admin-accent); }

    /* Sidebar styles */
    aside {
      background: linear-gradient(180deg, var(--sidebar-bg-start) 0%, var(--sidebar-bg-end) 100%);
      box-shadow: 5px 0 15px rgba(0,0,0,0.2);
      border-right: 1px solid rgba(255,255,255,0.05);
    }
    aside .nav-button {
      background-color: transparent;
      color: var(--text-light);
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      border-radius: 0.75rem;
    }
    aside .nav-button::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 0;
      height: 100%;
      background: linear-gradient(90deg, rgba(86,200,216,0.2) 0%, transparent 100%);
      transition: width 0.3s ease;
      z-index: 0;
    }
    aside .nav-button.active {
      color: var(--admin-primary);
      background-color: rgba(86,200,216,0.1);
      box-shadow: inset 3px 0 0 var(--admin-primary);
    }
    aside .nav-button.active::before {
      width: 100%;
    }
    aside .nav-button:hover:not(.active) {
      color: #e2e8f0;
      background-color: rgba(255,255,255,0.05);
    }
    aside .nav-button span {
      position: relative;
      z-index: 1;
    }
    aside .nav-button .fas {
      font-size: 1.1rem;
      color: #a0aec0;
      transition: color 0.3s ease;
      position: relative;
      z-index: 1;
    }
    aside .nav-button.active .fas {
      color: var(--admin-primary);
    }

    /* Main content section display */
    .section {
      display: none;
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.5s ease-out, transform 0.5s ease-out;
    }
    .section.active {
      display: block;
      opacity: 1;
      transform: translateY(0);
    }

    /* Dashboard Cards */
    .dashboard-card {
      background: linear-gradient(145deg, var(--card-bg-start), var(--card-bg-end));
      border-radius: 1.5rem;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
      position: relative;
    }
    .dashboard-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 35px rgba(0,0,0,0.12);
    }
    .dashboard-card-icon {
      font-size: 2.5rem;
      opacity: 0.2;
      position: absolute;
      top: 1rem;
      right: 1.5rem;
      color: currentColor;
    }
    .dashboard-card-value {
      font-size: 2.5rem;
      font-weight: 800;
      line-height: 1;
    }
    .dashboard-card-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #4a5568;
    }
    .dashboard-card-trend {
      font-size: 0.875rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    /* Table styling */
    .admin-table {
      width: 100%;
      border-collapse: separate; /* Use separate to allow border-radius on cells */
      border-spacing: 0; /* Remove default spacing */
      border-radius: 1rem; /* Apply rounded corners to the whole table */
      overflow: hidden; /* Hide content that overflows rounded corners */
    }

    .admin-table th {
        background-color: var(--card-bg-end);
        color: var(--text-dark);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        padding: 1rem 1.25rem;
        border-bottom: 2px solid #e2e8f0;
        text-align: left; /* Align headers left */
    }
    .admin-table td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #edf2f7;
        color: #4a5568;
        font-size: 0.95rem;
    }
    .admin-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .admin-table tbody tr:last-child td {
        border-bottom: none;
    }
    /* Specific styling for first and last cells in header/body for rounded corners */
    .admin-table thead th:first-child { border-top-left-radius: 1rem; }
    .admin-table thead th:last-child { border-top-right-radius: 1rem; }
    .admin-table tbody tr:last-child td:first-child { border-bottom-left-radius: 1rem; }
    .admin-table tbody tr:last-child td:last-child { border-bottom-right-radius: 1rem; }


    /* Badge styles */
    .badge {
      display: inline-block;
      padding: 0.3em 0.7em;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      color: white;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .badge-green { background-color: #34d399; }
    .badge-yellow { background-color: #fbbf24; }
    .badge-red { background-color: #ef4444; }
    .badge-blue { background-color: #60a5fa; }
    .badge-purple { background-color: #a78bfa; }

    /* Modal styles */
    .modal-backdrop {
      background-color: rgba(0,0,0,0.6);
      backdrop-filter: blur(8px); /* Increased blur */
      -webkit-backdrop-filter: blur(8px);
      transition: opacity 0.3s ease;
    }
    .modal-content {
      background: linear-gradient(160deg, #ffffff, #f8f8f8);
      border-radius: 1.5rem;
      box-shadow: 0 15px 40px rgba(0,0,0,0.25); /* Stronger shadow */
      transform: scale(0.9); /* Slightly smaller initial scale */
      opacity: 0;
      transition: transform 0.4s ease-out, opacity 0.4s ease-out; /* Slower transition */
      max-height: 90vh; /* MODIFICATION: Ensure modal doesn't exceed viewport height */
      overflow-y: auto;  /* MODIFICATION: Allow vertical scrolling if content overflows */
    }
    .modal-open .modal-content {
      transform: scale(1);
      opacity: 1;
    }
    .modal-button-primary {
      background-color: var(--admin-primary);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 0.75rem;
      font-weight: 600;
      transition: all 0.2s ease;
      display: inline-flex; /* For icon alignment */
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .modal-button-primary:hover {
      background-color: var(--admin-primary-dark);
      transform: translateY(-2px); /* Increased hover effect */
      box-shadow: 0 4px 12px rgba(86,200,216,0.4); /* Stronger shadow on hover */
    }
    .modal-button-secondary {
      background-color: #e2e8f0;
      color: #4a5568;
      padding: 0.75rem 1.5rem;
      border-radius: 0.75rem;
      font-weight: 500;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .modal-button-secondary:hover {
      background-color: #cbd5e0;
      transform: translateY(-2px);
    }
    .modal-button-danger {
      background-color: var(--admin-accent);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 0.75rem;
      font-weight: 600;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .modal-button-danger:hover {
      background-color: var(--admin-accent-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(239,68,68,0.4);
    }

    /* Input fields */
    input[type="text"], input[type="email"], input[type="number"], input[type="password"], select, textarea, input[type="file"] {
      border: 1px solid #cbd5e0;
      border-radius: 0.75rem; /* More rounded */
      padding: 0.85rem 1.15rem; /* Slightly more padding */
      transition: all 0.2s ease;
      font-size: 1rem; /* Readable font size */
    }
    input[type="text"]:focus, input[type="email"]:focus, input[type="number"]:focus, input[type="password"]:focus, select:focus, textarea:focus, input[type="file"]:focus {
      outline: none;
      border-color: var(--admin-primary);
      box-shadow: 0 0 0 4px rgba(86,200,216,0.3); /* Stronger focus ring */
    }
    textarea {
        min-height: 80px; /* Default height for textareas */
        resize: vertical; /* Allow vertical resizing */
    }
    /* Specific style for file input for better appearance */
    input[type="file"] {
      padding: 0.5rem 1rem; /* Adjust padding for file input */
      background-color: #f8fafc;
    }

    /* Chart container for responsiveness */
    .chart-container {
        position: relative;
        height: 40vh; /* Responsive height */
        width: 100%;
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
  </style>
  <script>
    // Function to show specific section
    function showSection(id) {
      document.querySelectorAll('.section').forEach(s => {
        s.classList.remove('active');
        s.style.display = 'none'; // Ensure display is none for inactive sections
      });

      const targetSection = document.getElementById(id);
      if (targetSection) {
        targetSection.style.display = 'block'; // Set display to block immediately
        // Use requestAnimationFrame to ensure the display change is rendered before adding 'active'
        requestAnimationFrame(() => {
          targetSection.classList.add('active'); // Add active class to trigger transition
        });

        // Update active state in sidebar
        document.querySelectorAll('.nav-button').forEach(btn => btn.classList.remove('active'));
        const activeButton = document.querySelector(`.nav-button[onclick="showSection('${id}')"]`);
        if (activeButton) {
          activeButton.classList.add('active');
        }

        // Always save the active section to localStorage
        localStorage.setItem('adminSection', id);
      }
    }

    // On page load, show the last active section from localStorage or 'dashboard'
    window.addEventListener('DOMContentLoaded', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const sectionFromUrl = urlParams.get('section');
      const lastActiveSection = localStorage.getItem('adminSection');

      // Prioritize URL parameter, then localStorage, then default to 'dashboard'
      if (sectionFromUrl) {
          showSection(sectionFromUrl);
      } else if (lastActiveSection) {
          showSection(lastActiveSection);
      } else {
          showSection('dashboard'); // Default fallback
      }

      // Auto-dismiss messages
      setTimeout(() => {
        document.querySelectorAll('.bg-green-100, .bg-red-100').forEach(el => {
          el.classList.add('opacity-0');
          setTimeout(() => el.remove(), 500);
        });
      }, 3500);

      // Event listener for "Add New Category" option
      const addCategorySelect = document.getElementById('add-category-select');
      const newCategoryField = document.getElementById('new-category-field');
      const newCategoryInput = document.getElementById('add-new-category-input');

      if (addCategorySelect) {
        addCategorySelect.addEventListener('change', function() {
          if (this.value === 'new_category_option') {
            newCategoryField.style.display = 'block';
            newCategoryInput.setAttribute('required', 'required'); // Make new category input required if chosen
          } else {
            newCategoryField.style.display = 'none';
            newCategoryInput.removeAttribute('required');
            newCategoryInput.value = ''; // Clear the new category input
          }
        });
      }

      // Event listener for reviews filter
      const filterStarsSelect = document.getElementById('filter-stars-select');
      if (filterStarsSelect) {
          filterStarsSelect.addEventListener('change', function() {
              const currentUrl = new URL(window.location.href);
              currentUrl.searchParams.set('section', 'reviews'); // Ensure we stay on reviews section
              if (this.value) {
                  currentUrl.searchParams.set('filter_stars', this.value);
              } else {
                  currentUrl.searchParams.delete('filter_stars');
              }
              currentUrl.searchParams.set('reviews_page', 1); // Reset to first page on filter change
              window.location.href = currentUrl.toString();
          });
      }
    });


    // Product Modals
    function openEditModal(product) {
      document.getElementById('edit-id').value = product.id;
      document.getElementById('edit-name').value = product.name;
      // Ensure description is populated
      document.getElementById('edit-description').value = product.description || '';
      // Set the image preview
      const currentImage = product.image ? `uploads/${product.image}` : 'https://placehold.co/100x100/E0F7FA/56C8D8?text=No+Image';
      document.getElementById('edit-current-image').src = currentImage;
      document.getElementById('edit-current-image').alt = product.name;

      document.getElementById('edit-return-category').value = new URLSearchParams(window.location.search).get('category') || '';
      document.getElementById('edit-category').value = product.category;
      document.getElementById('edit-price').value = product.price;
      document.getElementById('edit-stock').value = product.stock;
      document.getElementById('editModal').classList.remove('hidden');
      document.getElementById('editModal').classList.add('flex', 'modal-open'); // Add modal-open for animation
    }

    function closeEditModal() {
      document.getElementById('editModal').classList.remove('flex', 'modal-open');
      document.getElementById('editModal').classList.add('hidden');
      // Clear file input value to prevent accidental resubmission of same file
      document.getElementById('edit-image-file').value = '';
    }

    function openDeleteModal(id, name) {
      document.getElementById('delete-id').value = id;
      document.getElementById('delete-message').textContent = `Are you sure you want to delete "${name}"? This action cannot be undone.`;
      document.getElementById('delete-return-category').value = new URLSearchParams(window.location.search).get('category') || '';
      document.getElementById('deleteModal').classList.remove('hidden');
      document.getElementById('deleteModal').classList.add('flex', 'modal-open');
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.remove('flex', 'modal-open');
      document.getElementById('deleteModal').classList.add('hidden');
    }

    function openAddModal() {
      // Clear previous form data when opening add modal
      document.getElementById('addModal').querySelector('form').reset();
      // Hide new category field and remove required attribute
      document.getElementById('new-category-field').style.display = 'none';
      document.getElementById('add-new-category-input').removeAttribute('required');
      document.getElementById('addModal').classList.remove('hidden');
      document.getElementById('addModal').classList.add('flex', 'modal-open');
    }

    function closeAddModal() {
      document.getElementById('addModal').classList.remove('flex', 'modal-open');
      document.getElementById('addModal').classList.add('hidden');
    }

    // Order Modals
    function openOrderDetailsModal(orderId) {
      document.getElementById('viewOrderModal').classList.remove('hidden');
      document.getElementById('viewOrderModal').classList.add('flex', 'modal-open');
      document.getElementById('order-details-content').innerHTML = 'Loading...'; // Reset content

      fetch('fetch_order_details.php?id=' + orderId)
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok.');
            return res.text();
        })
        .then(html => {
          document.getElementById('order-details-content').innerHTML = html;
        })
        .catch(error => {
          console.error('Error fetching order details:', error);
          document.getElementById('order-details-content').innerHTML = "<p class='text-red-500'>Failed to load order details. Please try again.</p>";
        });
    }

    function closeOrderDetailsModal() {
      document.getElementById('viewOrderModal').classList.remove('flex', 'modal-open');
      document.getElementById('viewOrderModal').classList.add('hidden');
    }

    function openUpdateStatusModal(orderId, currentStatus) {
      document.getElementById('update-order-id').value = orderId;
      document.getElementById('update-order-status').value = currentStatus;
      document.getElementById('updateStatusModal').classList.remove('hidden');
      document.getElementById('updateStatusModal').classList.add('flex', 'modal-open');
    }

    function closeUpdateStatusModal() {
      document.getElementById('updateStatusModal').classList.remove('flex', 'modal-open');
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
        .then(res => res.json()) // Expect JSON response
        .then(data => {
          if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
          } else {
            showMessage(data.message || 'Failed to update status.', 'error');
          }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            showMessage("An error occurred while updating the status.", 'error');
        });
    }

    // User Management Functions (Updated deleteUser implementation to handle JSON)
    function deleteUser(userId, username) {
        if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
            fetch('delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${userId}`
            })
            .then(res => res.json()) // Expect JSON response
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => location.reload(), 1500); // Reload to reflect changes after a short delay
                } else {
                    showMessage(data.message || 'Failed to delete user.', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                showMessage("An error occurred while deleting the user.", 'error');
            });
        }
    }

    // Review Management Functions (Renamed and fixed parameter, updated to handle JSON)
    function updateReviewStatus(reviewId, newStatus) { // Renamed from updateReviewFeaturedStatus
        fetch('update_review_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `review_id=${reviewId}&new_status=${newStatus}` // Changed parameter name to new_status
        })
        .then(res => res.json()) // Expect JSON response
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                setTimeout(() => location.reload(), 1500); // Reload to reflect changes after a short delay
            } else {
                showMessage(data.message || 'Failed to update review status.', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating review status:', error);
            showMessage("An error occurred while updating the review status.", 'error');
        });
    }

    function deleteReview(reviewId, username) {
        if (confirm(`Are you sure you want to delete the review by ${username}?`)) {
            fetch('delete_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${reviewId}` // Changed to 'id' to match the reviews table column
            })
            .then(res => res.json()) // Expect JSON response
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => location.reload(), 1500); // Reload to reflect changes after a short delay
                } else {
                    showMessage(data.message || 'Failed to delete review.', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting review:', error);
                showMessage("An error occurred while deleting the review.", 'error');
            });
        }
    }

    // Admin Profile Modals
    function openAdminEditProfileModal() {
        document.getElementById('admin-edit-username').value = '<?= htmlspecialchars($admin_details['username'] ?? '') ?>';
        document.getElementById('admin-edit-fname').value = '<?= htmlspecialchars($admin_details['fname'] ?? '') ?>';
        document.getElementById('admin-edit-lname').value = '<?= htmlspecialchars($admin_details['lname'] ?? '') ?>';
        document.getElementById('admin-edit-phone').value = '<?= htmlspecialchars($admin_details['phone'] ?? '') ?>';
        document.getElementById('adminEditProfileModal').classList.remove('hidden');
        document.getElementById('adminEditProfileModal').classList.add('flex', 'modal-open');
    }

    function closeAdminEditProfileModal() {
        document.getElementById('adminEditProfileModal').classList.remove('flex', 'modal-open');
        document.getElementById('adminEditProfileModal').classList.add('hidden');
    }

    function openAdminChangeEmailModal() {
        document.getElementById('admin-change-email-current').value = '<?= htmlspecialchars($admin_details['email'] ?? '') ?>';
        document.getElementById('admin-change-email-new').value = ''; // Clear new email input
        document.getElementById('admin-email-otp').value = ''; // Clear OTP input
        document.getElementById('adminEmailOtpMessage').textContent = ''; // Clear message
        document.getElementById('sendAdminEmailOtpButton').textContent = 'Send OTP'; // Reset button text
        document.getElementById('sendAdminEmailOtpButton').disabled = false; // Enable button
        document.getElementById('adminChangeEmailModal').classList.remove('hidden');
        document.getElementById('adminChangeEmailModal').classList.add('flex', 'modal-open');
    }

    function closeAdminChangeEmailModal() {
        document.getElementById('adminChangeEmailModal').classList.remove('flex', 'modal-open');
        document.getElementById('adminChangeEmailModal').classList.add('hidden');
    }

    function openAdminChangePasswordModal() {
        document.getElementById('admin-change-password-email').value = '<?= htmlspecialchars($admin_details['email'] ?? '') ?>';
        document.getElementById('admin-new-password').value = ''; // Clear new password
        document.getElementById('admin-confirm-password').value = ''; // Clear confirm password
        document.getElementById('admin-password-otp').value = ''; // Clear OTP
        document.getElementById('adminPasswordOtpMessage').textContent = ''; // Clear message
        document.getElementById('sendAdminPasswordOtpButton').textContent = 'Send OTP'; // Reset button text
        document.getElementById('sendAdminPasswordOtpButton').disabled = false; // Enable button
        document.getElementById('adminChangePasswordModal').classList.remove('hidden');
        document.getElementById('adminChangePasswordModal').classList.add('flex', 'modal-open');
    }

    function closeAdminChangePasswordModal() {
        document.getElementById('adminChangePasswordModal').classList.remove('flex', 'modal-open');
        document.getElementById('adminChangePasswordModal').classList.add('hidden');
    }

    // Admin Email Change OTP Logic
    async function sendAdminEmailOtp() {
        const newEmail = document.getElementById('admin-change-email-new').value;
        const sendOtpButton = document.getElementById('sendAdminEmailOtpButton');
        const otpMessageDiv = document.getElementById('adminEmailOtpMessage');

        if (!newEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) {
            otpMessageDiv.textContent = 'Please enter a valid new email address.';
            otpMessageDiv.className = 'text-sm mt-1 text-red-600';
            return;
        }

        sendOtpButton.disabled = true;
        sendOtpButton.textContent = 'Sending...';
        otpMessageDiv.textContent = ''; // Clear previous messages

        try {
            const response = await fetch('send_email_verification_otp_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `new_email=${encodeURIComponent(newEmail)}`
            });
            const data = await response.json();

            if (data.success) {
                otpMessageDiv.textContent = data.message || 'OTP sent to your current email! Check your inbox (and spam).';
                otpMessageDiv.className = 'text-sm mt-1 text-green-600';
            } else {
                otpMessageDiv.textContent = data.message || 'Failed to send OTP. Please try again.';
                otpMessageDiv.className = 'text-sm mt-1 text-red-600';
            }
        } catch (error) {
            console.error('Error sending admin email OTP:', error);
            otpMessageDiv.textContent = 'An error occurred. Failed to send OTP.';
            otpMessageDiv.className = 'text-sm mt-1 text-red-600';
        } finally {
            sendOtpButton.disabled = false;
            sendOtpButton.textContent = 'Resend OTP';
        }
    }

    // Admin Email Update with OTP Logic
    async function submitAdminEmailChange() {
        event.preventDefault(); // Prevent default form submission
        const enteredOtp = document.getElementById('admin-email-otp').value;
        const newEmail = document.getElementById('admin-change-email-new').value;
        const otpMessageDiv = document.getElementById('adminEmailOtpMessage');
        const submitButton = document.querySelector('#adminChangeEmailModal button[type="submit"]');

        if (!enteredOtp) {
            otpMessageDiv.textContent = 'Please enter the OTP.';
            otpMessageDiv.className = 'text-sm mt-1 text-red-600';
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Verifying...';
        otpMessageDiv.textContent = '';

        try {
            const response = await fetch('update_admin_email_with_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `otp=${encodeURIComponent(enteredOtp)}&new_email_submitted=${encodeURIComponent(newEmail)}`
            });
            const data = await response.json();

            if (data.success) {
                otpMessageDiv.textContent = data.message;
                otpMessageDiv.className = 'text-sm mt-1 text-green-600';
                setTimeout(() => {
                    closeAdminChangeEmailModal();
                    location.reload(); // Reload to show updated email
                }, 1500);
            } else {
                otpMessageDiv.textContent = data.message || 'Failed to update email. Please try again.';
                otpMessageDiv.className = 'text-sm mt-1 text-red-600';
            }
        } catch (error) {
            console.error('Error updating admin email:', error);
            otpMessageDiv.textContent = 'An error occurred. Failed to update email.';
            otpMessageDiv.className = 'text-sm mt-1 text-red-600';
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Verify & Change Email';
        }
    }

    // Admin Password Change OTP Logic
    async function sendAdminPasswordOtp() {
        const email = document.getElementById('admin-change-password-email').value;
        const sendOtpButton = document.getElementById('sendAdminPasswordOtpButton');
        const otpMessageDiv = document.getElementById('adminPasswordOtpMessage');

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            otpMessageDiv.textContent = 'Please enter a valid email address.';
            otpMessageDiv.className = 'text-sm mt-1 text-red-600';
            return;
        }

        sendOtpButton.disabled = true;
        sendOtpButton.textContent = 'Sending...';
        otpMessageDiv.textContent = '';

        try {
            const response = await fetch('send_password_reset_otp_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}`
            });
            const data = await response.json();

            if (data.success) {
                otpMessageDiv.textContent = data.message || 'OTP sent to your email! Check your inbox (and spam).';
                otpMessageDiv.className = 'text-sm mt-1 text-green-600';
            } else {
                otpMessageDiv.textContent = data.message || 'Failed to send OTP. Please try again.';
                otpMessageDiv.className = 'text-sm mt-1 text-red-600';
            }
        } catch (error) {
            console.error('Error sending admin password OTP:', error);
            otpMessageDiv.textContent = 'An error occurred. Failed to send OTP.';
            otpMessageDiv.className = 'text-sm mt-1 text-red-600';
        } finally {
            sendOtpButton.disabled = false;
            sendOtpButton.textContent = 'Resend OTP';
        }
    }

    // Admin Password Update with OTP Logic
    async function submitAdminPasswordChange() {
        event.preventDefault(); // Prevent default form submission
        const email = document.getElementById('admin-change-password-email').value;
        const newPassword = document.getElementById('admin-new-password').value;
        const confirmPassword = document.getElementById('admin-confirm-password').value;
        const enteredOtp = document.getElementById('admin-password-otp').value;
        const otpMessageDiv = document.getElementById('adminPasswordOtpMessage');
        const submitButton = document.querySelector('#adminChangePasswordModal button[type="submit"]');

        if (newPassword !== confirmPassword) {
            otpMessageDiv.textContent = 'New password and confirm password do not match.';
            otpMessageDiv.className = 'text-sm mt-1 text-red-600';
            return;
        }
        if (!enteredOtp) {
            otpMessageDiv.textContent = 'Please enter the OTP.';
            otpMessageDiv.className = 'text-sm mt-1 text-red-600';
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Updating...';
        otpMessageDiv.textContent = '';

        try {
            const response = await fetch('update_admin_password_with_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&new_password=${encodeURIComponent(newPassword)}&otp=${encodeURIComponent(enteredOtp)}`
            });
            const data = await response.json();

            if (data.success) {
                otpMessageDiv.textContent = data.message;
                otpMessageDiv.className = 'text-sm mt-1 text-green-600';
                setTimeout(() => {
                    closeAdminChangePasswordModal();
                    // Optionally, redirect to login or show success message
                    location.reload();
                }, 1500);
            } else {
                otpMessageDiv.textContent = data.message || 'Failed to update password. Please try again.';
                otpMessageDiv.className = 'text-sm mt-1 text-red-600';
            }
        } catch (error) {
            console.error('Error updating admin password:', error);
            otpMessageDiv.textContent = 'An error occurred. Failed to update password.';
            otpMessageDiv.className = 'text-sm mt-1 text-red-600';
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Update Password';
        }
    }

    // Attach event listeners for admin profile forms
    document.addEventListener('DOMContentLoaded', () => {
        const adminEditProfileForm = document.getElementById('adminEditProfileForm');
        if (adminEditProfileForm) {
            adminEditProfileForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                formData.append('admin_id', '<?= $_SESSION['id'] ?>'); // Ensure admin ID is sent

                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Saving...';

                try {
                    const response = await fetch('update_admin_profile.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData).toString(), // Use URLSearchParams for x-www-form-urlencoded
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        showMessage(data.message, 'success');
                        setTimeout(() => {
                            closeAdminEditProfileModal();
                            location.reload(); // Reload to reflect changes
                        }, 1500);
                    } else {
                        showMessage(data.message || 'Failed to update profile.', 'error');
                    }
                } catch (error) {
                    console.error('Error updating admin profile:', error);
                    showMessage('Network error. Could not update profile.', 'error');
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Save Changes';
                }
            });
        }

        const adminChangeEmailForm = document.getElementById('adminChangeEmailForm');
        if (adminChangeEmailForm) {
            adminChangeEmailForm.addEventListener('submit', submitAdminEmailChange);
        }

        const adminChangePasswordForm = document.getElementById('adminChangePasswordForm');
        if (adminChangePasswordForm) {
            adminChangePasswordForm.addEventListener('submit', submitAdminPasswordChange);
        }
    });

  </script>
</head>

<body class="antialiased">
  <div class="flex h-screen">
    <aside class="w-full lg:w-72 p-6 space-y-8 flex flex-col shadow-xl">
      <div class="px-3 py-4 border-b border-gray-700 text-center">
        <h2 class="text-4xl dancing-script-font font-bold text-white flex items-center justify-center gap-2">
          <i class="fas fa-magic text-admin-primary"></i> Admin Panel
        </h2>
      </div>

      <nav class="flex flex-col gap-3 flex-grow">
        <button onclick="showSection('dashboard')"
          class="nav-button flex items-center space-x-4 px-5 py-3 text-lg group">
          <i class="fas fa-tachometer-alt group-hover:scale-110 transition-transform duration-200"></i> <span>Dashboard</span>
        </button>

        <button onclick="showSection('users')"
          class="nav-button flex items-center space-x-4 px-5 py-3 text-lg group">
          <i class="fas fa-users group-hover:scale-110 transition-transform duration-200"></i> <span>Manage Users</span>
        </button>

        <button onclick="showSection('products')"
          class="nav-button flex items-center space-x-4 px-5 py-3 text-lg group">
          <i class="fas fa-box-open group-hover:scale-110 transition-transform duration-200"></i> <span>Manage Prod</span>
        </button>

        <button onclick="showSection('orders')"
          class="nav-button flex items-center space-x-4 px-5 py-3 text-lg group">
          <i class="fas fa-receipt group-hover:scale-110 transition-transform duration-200"></i> <span>Manage Orders</span>
        </button>

        
        <button onclick="showSection('archive')"
          class="nav-button flex items-center space-x-4 px-5 py-3 text-lg group">
          <i class="fas fa-archive group-hover:scale-110 transition-transform duration-200"></i> <span>Archive Orders</span>
        </button>

        <button onclick="showSection('reviews')"
        class="nav-button flex items-center space-x-4 px-5 py-3 text-lg group">
        <i class="fas fa-star group-hover:scale-110 transition-transform duration-200"></i> <span>Manage Reviews</span>
      </button>
      <button onclick="showSection('admin-profile')"
        class="nav-button flex items-center space-x-4 px-5 py-3 text-lg group">
        <i class="fas fa-user-cog group-hover:scale-110 transition-transform duration-200"></i> <span>Edit Profile</span>
      </button>
      </nav>

      <a href="logout.php"
        class="nav-button flex items-center space-x-4 px-5 py-3 text-lg text-red-400 hover:text-red-300">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
      </a>
    </aside>


    <main class="flex-1 px-8 py-8 md:px-12 overflow-y-auto">
      <section id="dashboard" class="section active mb-10">
        <div class="bg-white rounded-3xl shadow-lg p-8 mb-8 border border-gray-100">
          <h1 class="text-4xl lg:text-5xl font-extrabold text-gray-900 mb-4 pacifico-font">
            Welcome, Admin <span class="text-admin-primary">👋</span>
          </h1>
          <p class="text-gray-600 text-lg">
            Your magical command center. Here’s a quick overview of your store's performance.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6 mb-10">
          <div class="dashboard-card p-6 text-admin-primary flex flex-col justify-between">
            <div>
                <p class="dashboard-card-title">Total Users</p>
                <p class="dashboard-card-value"><?= $total_users ?></p>
            </div>
            <p class="dashboard-card-trend text-green-600 mt-2"><i class="fas fa-arrow-up"></i> 5% this month</p>
            <i class="fas fa-users dashboard-card-icon"></i>
          </div>
          <div class="dashboard-card p-6 text-admin-accent flex flex-col justify-between">
            <div>
                <p class="dashboard-card-title">Total Products</p>
                <p class="dashboard-card-value"><?= $total_products ?></p>
            </div>
            <p class="dashboard-card-trend text-green-600 mt-2"><i class="fas fa-arrow-up"></i> 2% new this week</p>
            <i class="fas fa-box-open dashboard-card-icon"></i>
          </div>
          <div class="dashboard-card p-6 text-blue-500 flex flex-col justify-between">
            <div>
                <p class="dashboard-card-title">Total Orders</p>
                <p class="dashboard-card-value"><?= $total_orders ?></p>
            </div>
            <p class="dashboard-card-trend text-green-600 mt-2"><i class="fas fa-arrow-up"></i> 8% increase</p>
            <i class="fas fa-receipt dashboard-card-icon"></i>
          </div>
          <div class="dashboard-card p-6 text-green-600 flex flex-col justify-between">
            <div>
                <p class="dashboard-card-title">Total Revenue</p>
                <p class="dashboard-card-value">DA <?= number_format($total_revenue, 2) ?></p>
            </div>
            <p class="dashboard-card-trend text-green-600 mt-2"><i class="fas fa-chart-line"></i> Steady growth</p>
            <i class="fas fa-dollar-sign dashboard-card-icon"></i>
          </div>
          <div class="dashboard-card p-6 text-yellow-600 flex flex-col justify-between">
            <div>
                <p class="dashboard-card-title">Pending Orders</p>
                <p class="dashboard-card-value"><?= $pending_orders ?></p>
            </div>
            <p class="dashboard-card-trend text-red-600 mt-2"><i class="fas fa-exclamation-triangle"></i> Action required!</p>
            <i class="fas fa-hourglass-half dashboard-card-icon"></i>
          </div>
          <div class="dashboard-card p-6 text-red-600 flex flex-col justify-between">
            <div>
                <p class="dashboard-card-title">Out of Stock</p>
                <p class="dashboard-card-value"><?= $out_of_stock_products ?></p>
            </div>
            <p class="dashboard-card-trend text-red-600 mt-2"><i class="fas fa-arrow-up"></i> Urgent restock needed</p>
            <i class="fas fa-exclamation-circle dashboard-card-icon"></i>
          </div>
        </div>

        <div class="bg-white rounded-3xl shadow-lg p-8 border border-gray-100">
            <h3 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-4 border-gray-100">Products by Category Overview</h3>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels: <?= json_encode(array_keys($category_counts)) ?>,
                datasets: [{
                  label: 'Number of Products',
                  data: <?= json_encode(array_values($category_counts)) ?>,
                  backgroundColor: [
                      '#56c8d8',
                      '#ef4444',
                      '#60a5fa',
                      '#34d399',
                      '#a78bfa',
                      '#facc15',
                      '#f97316', /* Orange */
                      '#8b5cf6', /* Indigo */
                      '#ec4899', /* Pink */
                      '#10b981'  /* Emerald */
                  ],
                  borderColor: [
                      '#56c8d8',
                      '#ef4444',
                      '#60a5fa',
                      '#34d399',
                      '#a78bfa',
                      '#facc15',
                      '#f97316',
                      '#8b5cf6',
                      '#ec4899',
                      '#10b981'
                  ],
                  borderWidth: 1
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  y: {
                    beginAtZero: true,
                    grid: {
                      color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                      color: '#6b7280',
                      precision: 0 // Ensure integer ticks for count
                    }
                  },
                  x: {
                    grid: {
                      color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                      color: '#6b7280'
                    }
                  }
                },
                plugins: {
                  legend: {
                    display: false
                  },
                  tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#56c8d8',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12
                  }
                }
              }
            });
          });
        </script>


        <div class="bg-white rounded-3xl shadow-lg p-8 border border-gray-100 mt-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-4 border-gray-100">Quick Actions</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <button onclick="showSection('products'); openAddModal();" class="modal-button-primary">
                    <i class="fas fa-plus-circle"></i> Add New Product
                </button>
                <button onclick="showSection('orders');" class="modal-button-primary">
                    <i class="fas fa-clipboard-list"></i> View All Orders
                </button>
                <button onclick="showSection('reviews');" class="modal-button-primary">
                    <i class="fas fa-star"></i> Manage Reviews
                </button>
            </div>
        </div>
      </section>

      <section id="users" class="section mb-6">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
          <h2 class="text-3xl font-bold text-gray-900 mb-4 pacifico-font">Manage Users</h2>
          <p class="text-gray-600 text-base mb-6">View, edit or remove users.</p>
          <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-md">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Full Name</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $users->fetch_assoc()): ?>
                  <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                      <?php
                        $roleBadge = match ($row['role']) {
                            'admin' => 'badge-purple',
                            'delivery person' => 'badge-blue',
                            default => 'badge-green'
                        };
                      ?>
                      <span class="badge <?= $roleBadge ?>"><?= ucfirst($row['role']) ?></span>
                    </td>
                    <td class="text-center">
                      <?php if ($row['role'] === 'client' || $row['role'] === 'delivery person'): ?>
                        <button onclick="deleteUser(<?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>')" class="text-red-500 hover:text-red-700 font-medium transition-colors">
                            <i class="fas fa-trash-alt mr-1"></i> Delete
                        </button>
                      <?php else: ?>
                        <span class="text-gray-400 text-sm">No actions for admins</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section id="products" class="section mb-6">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
          <h2 class="text-3xl font-bold text-gray-900 mb-4 pacifico-font">Manage Products</h2>
          <p class="text-gray-600 text-base mb-6">Browse, edit, and manage product listings. Use filters or search to
            refine.</p>

          <?php if (isset($_GET['updated'])): ?>
            <div
              class="flex items-center gap-3 px-4 py-3 bg-green-100 border-l-4 border-green-500 text-green-800 rounded-lg mb-4 shadow-md">
              <i class="fas fa-check-circle text-green-600 text-xl"></i>
              <span class="font-medium">Product updated successfully!</span>
            </div>
          <?php elseif (isset($_GET['deleted'])): ?>
            <div class="flex items-center gap-3 px-4 py-3 bg-red-100 border-l-4 border-red-500 text-red-800 rounded-lg mb-4 shadow-md">
              <i class="fas fa-trash-alt text-red-600 text-xl"></i>
              <span class="font-medium">Product deleted successfully!</span>
            </div>
          <?php elseif (isset($_GET['added'])): ?>
             <div class="flex items-center gap-3 px-4 py-3 bg-green-100 border-l-4 border-green-500 text-green-800 rounded-lg mb-4 shadow-md">
              <i class="fas fa-plus-circle text-green-600 text-xl"></i>
              <span class="font-medium">Product added successfully!</span>
            </div>
          <?php endif; ?>


          <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
              <label for="category" class="text-gray-700 font-medium whitespace-nowrap">Filter by:</label>
              <select name="category" onchange="this.form.submit()" class="border rounded-lg px-4 py-2 w-full focus:ring-admin-primary focus:border-admin-primary">
                <option value="">All Categories</option>
                <?php foreach ($all_categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>" <?= $cat === $category_filter ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="section" value="products">
            </form>

            <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
              <input type="text" name="search"
                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                placeholder="Search by name..." class="border px-4 py-2 rounded-lg w-full focus:ring-admin-primary focus:border-admin-primary">
              <button type="submit" class="modal-button-primary px-4 py-2">
                <i class="fas fa-search"></i> <span class="hidden md:inline">Search</span>
              </button>
              <input type="hidden" name="section" value="products">
            </form>
          </div>

          <div class="flex justify-end mb-4">
            <button onclick="openAddModal()"
              class="modal-button-primary">
              <i class="fas fa-plus-circle"></i> Add Product
            </button>
          </div>

          <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-md">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Category</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1 + ($page - 1) * $limit; ?>
                <?php while ($prod = $products->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <img src="uploads/<?= htmlspecialchars($prod['image'] ?? 'https://placehold.co/50x50/E0F7FA/56C8D8?text=Gift') ?>"
                             alt="<?= htmlspecialchars($prod['name']) ?>"
                             class="w-16 h-16 object-cover rounded-md shadow-sm border border-gray-100">
                    </td>
                    <td><?= htmlspecialchars($prod['name']) ?></td>
                    <td><?= htmlspecialchars($prod['category']) ?></td>
                    <td>DA <?= number_format($prod['price'], 2) ?></td>
                    <td>
                      <?php
                      $stock = $prod['stock'];
                      $badgeColor = $stock == 0 ? 'badge-red' : ($stock < 5 ? 'badge-yellow' : 'badge-green');
                      $badgeLabel = $stock == 0 ? 'Out of Stock' : ($stock < 5 ? 'Low Stock' : 'In Stock');
                      ?>
                      <span class="badge <?= $badgeColor ?>">
                        <?= $stock ?> – <?= $badgeLabel ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <button onclick="openEditModal(<?= htmlspecialchars(json_encode($prod)) ?>)"
                        class="text-blue-500 hover:text-blue-700 font-medium transition-colors mr-2 p-2 rounded-md hover:bg-blue-50">
                        <i class="fas fa-edit"></i> Edit
                      </button>
                      <button onclick="openDeleteModal(<?= $prod['id'] ?>, '<?= htmlspecialchars($prod['name']) ?>')"
                        class="text-red-500 hover:text-red-700 font-medium transition-colors p-2 rounded-md hover:bg-red-50">
                        <i class="fas fa-trash-alt"></i> Delete
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
                <?php if ($total_products_paginated === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-500">No products found for this criteria.</td>
                    </tr>
                <?php endif; ?>
                <tr class="bg-gray-50 font-semibold">
                  <td class="px-4 py-3 text-gray-700" colspan="4">Total Products: <?= $total_products_paginated ?></td>
                  <td class="px-4 py-3 text-gray-700" colspan="2"></td>
                  <td></td>
                </tr>
              </tbody>
            </table>
          </div>

          <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex justify-center gap-3 text-sm font-medium">
              <?php
              $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
              $query = $_GET;
              $query['section'] = 'products'; // Ensure section is maintained for pagination
              ?>

              <?php if ($page > 1): ?>
                <?php $query['page'] = $page - 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-1">
                  <i class="fas fa-chevron-left"></i> Prev
                </a>
              <?php endif; ?>

              <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php $query['page'] = $p; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 rounded-lg transition-colors <?= $p == $page ? 'bg-admin-primary text-white shadow-md' : 'bg-gray-100 hover:bg-gray-200' ?>">
                  <?= $p ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $total_pages): ?>
                <?php $query['page'] = $page + 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-1">
                  Next <i class="fas fa-chevron-right"></i>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section id="orders" class="section">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
          <h2 class="text-3xl font-bold text-gray-900 mb-6 pacifico-font">Manage Orders</h2>
          <p class="text-gray-600 text-base mb-6">View and update the status of customer orders.</p>

          <div class="overflow-x-auto rounded-lg shadow-md bg-white border border-gray-200">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Email</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1 + ($order_page - 1) * $order_limit; ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($order['user_name']) ?></td>
                    <td><?= htmlspecialchars($order['email']) ?></td>
                    <td>DA <?= number_format($order['total_price'], 2) ?></td>
                    <td>
                      <?php
                      $badgeColor = match ($order['order_status']) {
                        'shipped' => 'badge-green',
                        'cancelled' => 'badge-red',
                        'completed' => 'badge-blue',
                        'validated' => 'badge-purple',
                        'returned' => 'badge-red',
                        default => 'badge-yellow'
                      };
                      ?>
                      <span class="badge <?= $badgeColor ?>">
                        <?= ucfirst($order['order_status']) ?>
                      </span>
                    </td>
                    <td><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                    <td class="text-center">
                      <button onclick="openOrderDetailsModal(<?= $order['id'] ?>)"
                        class="text-blue-500 hover:text-blue-700 font-medium transition-colors mr-2 p-2 rounded-md hover:bg-blue-50">
                        <i class="fas fa-eye"></i> View
                      </button>
                      <?php if ($order['order_status'] !== 'cancelled' && $order['order_status'] !== 'completed' && $order['order_status'] !== 'returned'): ?>
                        <button onclick="openUpdateStatusModal(<?= $order['id'] ?>, '<?= $order['order_status'] ?>')"
                          class="text-admin-primary hover:text-cyan-700 font-medium transition-colors p-2 rounded-md hover:bg-cyan-50">
                          <i class="fas fa-sync-alt"></i> Update
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
                 <?php if ($order_total === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-500">No orders found.</td>
                    </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if ($order_total_pages > 1): ?>
            <div class="mt-8 flex justify-center gap-3 text-sm font-medium">
              <?php
              $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
              $query = $_GET;
              $query['section'] = 'orders';
              ?>

              <?php if ($order_page > 1): ?>
                <?php $query['order_page'] = $order_page - 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-1">
                  <i class="fas fa-chevron-left"></i> Prev
                </a>
              <?php endif; ?>

              <?php for ($p = 1; $p <= $order_total_pages; $p++): ?>
                <?php $query['order_page'] = $p; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 rounded-lg transition-colors <?= $p == $order_page ? 'bg-admin-primary text-white shadow-md' : 'bg-gray-100 hover:bg-gray-200' ?>">
                  <?= $p ?>
                </a>
              <?php endfor; ?>

              <?php if ($order_page < $order_total_pages): ?>
                <?php $query['order_page'] = $order_page + 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-1">
                  Next <i class="fas fa-chevron-right"></i>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section id="archive" class="section">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
          <h2 class="text-3xl font-bold text-gray-900 mb-6 pacifico-font">Archived Orders</h2>
          <p class="text-gray-600 text-base mb-6">View completed and returned orders.</p>

          <div class="overflow-x-auto rounded-lg shadow-md bg-white border border-gray-200">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Email</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1 + ($archive_page - 1) * $archive_limit; ?>
                <?php while ($order = $archived_orders->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($order['user_name']) ?></td>
                    <td><?= htmlspecialchars($order['email']) ?></td>
                    <td>DA <?= number_format($order['total_price'], 2) ?></td>
                    <td>
                      <?php
                      $badgeColor = match ($order['order_status']) {
                        'completed' => 'badge-blue',
                        'returned' => 'badge-red',
                        default => 'badge-yellow' // Should not happen in this section
                      };
                      ?>
                      <span class="badge <?= $badgeColor ?>">
                        <?= ucfirst($order['order_status']) ?>
                      </span>
                    </td>
                    <td><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                    <td class="text-center">
                      <button onclick="openOrderDetailsModal(<?= $order['id'] ?>)"
                        class="text-blue-500 hover:text-blue-700 font-medium transition-colors mr-2 p-2 rounded-md hover:bg-blue-50">
                        <i class="fas fa-eye"></i> View
                      </button>
                      </td>
                  </tr>
                <?php endwhile; ?>
                 <?php if ($archive_total === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-500">No archived orders found.</td>
                    </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if ($archive_total_pages > 1): ?>
            <div class="mt-8 flex justify-center gap-3 text-sm font-medium">
              <?php
              $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
              $query = $_GET;
              $query['section'] = 'archive';
              ?>

              <?php if ($archive_page > 1): ?>
                <?php $query['archive_page'] = $archive_page - 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-1">
                  <i class="fas fa-chevron-left"></i> Prev
                </a>
              <?php endif; ?>

              <?php for ($p = 1; $p <= $archive_total_pages; $p++): ?>
                <?php $query['archive_page'] = $p; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 rounded-lg transition-colors <?= $p == $archive_page ? 'bg-admin-primary text-white shadow-md' : 'bg-gray-100 hover:bg-gray-200' ?>">
                  <?= $p ?>
                </a>
              <?php endfor; ?>

              <?php if ($archive_page < $archive_total_pages): ?>
                <?php $query['archive_page'] = $archive_page + 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-1">
                  Next <i class="fas fa-chevron-right"></i>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section id="admin-profile" class="section">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
            <h2 class="text-3xl font-bold text-gray-900 mb-4 pacifico-font">Admin Profile</h2>
            <p class="text-gray-600 text-base mb-6">Manage your personal details and security settings.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-6 rounded-lg shadow-inner border border-gray-100">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Personal Information</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500 font-medium">Username:</dt>
                            <dd class="text-gray-800 font-semibold"><?= htmlspecialchars($admin_details['username'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 font-medium">Email:</dt>
                            <dd class="text-gray-800 font-semibold"><?= htmlspecialchars($admin_details['email'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 font-medium">First Name:</dt>
                            <dd class="text-gray-800 font-semibold"><?= htmlspecialchars($admin_details['fname'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 font-medium">Last Name:</dt>
                            <dd class="text-gray-800 font-semibold"><?= htmlspecialchars($admin_details['lname'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 font-medium">Phone:</dt>
                            <dd class="text-gray-800 font-semibold"><?= htmlspecialchars($admin_details['phone'] ?? 'N/A') ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 font-medium">Member Since:</dt>
                            <dd class="text-gray-800 font-semibold">
                                <?= isset($admin_details['created_at']) ? date('F j, Y', strtotime($admin_details['created_at'])) : 'N/A' ?>
                            </dd>
                        </div>
                    </dl>
                    <button onclick="openAdminEditProfileModal()" class="modal-button-primary mt-6 w-full">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </button>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg shadow-inner border border-gray-100">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Security Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-700">Change your email address securely.</p>
                            <button onclick="openAdminChangeEmailModal()" class="modal-button-primary mt-2 w-full">
                                <i class="fas fa-envelope"></i> Change Email
                            </button>
                        </div>
                        <div>
                            <p class="text-gray-700">Update your password to keep your account secure.</p>
                            <button onclick="openAdminChangePasswordModal()" class="modal-button-primary mt-2 w-full">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </section>

      <section id="reviews" class="section">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
          <h2 class="text-3xl font-bold text-gray-900 mb-6 pacifico-font">Manage Reviews</h2>
          <p class="text-gray-600 text-base mb-6">Approve or reject customer reviews for display on the home page.</p>

          <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
              <label for="filter-stars-select" class="text-gray-700 font-medium whitespace-nowrap">Filter by Stars:</label>
              <select name="filter_stars" id="filter-stars-select" onchange="this.form.submit()" class="border rounded-lg px-4 py-2 w-full focus:ring-admin-primary focus:border-admin-primary">
                <option value="">All Stars</option>
                <option value="5" <?= $filter_stars == 5 ? 'selected' : '' ?>>5 Stars</option>
                <option value="4" <?= $filter_stars == 4 ? 'selected' : '' ?>>4 Stars</option>
                <option value="3" <?= $filter_stars == 3 ? 'selected' : '' ?>>3 Stars</option>
                <option value="2" <?= $filter_stars == 2 ? 'selected' : '' ?>>2 Stars</option>
                <option value="1" <?= $filter_stars == 1 ? 'selected' : '' ?>>1 Star</option>
              </select>
              <input type="hidden" name="section" value="reviews">
              <input type="hidden" name="reviews_page" value="1"> </form>
          </div>

          <div class="overflow-x-auto rounded-lg shadow-md bg-white border border-gray-200">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Order ID</th>
                  <th>Rating</th>
                  <th>Comment</th>
                  <th>Date</th>
                  <th>Approved</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1 + ($reviews_page - 1) * $reviews_limit; ?>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($review['username']) ?></td>
                    <td><a href="#" onclick="openOrderDetailsModal(<?= $review['order_id'] ?>)" class="text-blue-500 hover:underline"><?= $review['order_id'] ?></a></td>
                    <td>
                        <span class="text-yellow-500 text-lg">
                            <?php for ($s = 0; $s < $review['rating']; $s++) echo '★'; ?>
                            <?php for ($s = 0; $s < (5 - $review['rating']); $s++) echo '☆'; ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars(mb_strimwidth($review['comment'], 0, 70, "...")) ?></td>
                    <td><?= date('Y-m-d', strtotime($review['created_at'])) ?></td>
                    <td>
                        <?php
                            $approvedBadgeColor = $review['is_featured'] ? 'badge-green' : 'badge-red';
                            $approvedBadgeLabel = $review['is_featured'] ? 'Yes' : 'No';
                        ?>
                        <span class="badge <?= $approvedBadgeColor ?>"><?= $approvedBadgeLabel ?></span>
                    </td>
                    <td class="text-center">
                        <?php if (!$review['is_featured']): ?>
                            <button onclick="updateReviewStatus(<?= $review['id'] ?>, 1)"
                                class="text-admin-primary hover:text-cyan-700 font-medium transition-colors mr-2 p-2 rounded-md hover:bg-cyan-50">
                                <i class="fas fa-check-circle"></i> Approve
                            </button>
                        <?php else: ?>
                            <button onclick="updateReviewStatus(<?= $review['id'] ?>, 0)"
                                class="text-yellow-600 hover:text-yellow-700 font-medium transition-colors mr-2 p-2 rounded-md hover:bg-yellow-50">
                                <i class="fas fa-times-circle"></i> Unapprove
                            </button>
                        <?php endif; ?>
                        <button onclick="deleteReview(<?= $review['id'] ?>, '<?= htmlspecialchars($review['username']) ?>')"
                            class="text-red-500 hover:text-red-700 font-medium transition-colors p-2 rounded-md hover:bg-red-50">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
                 <?php if ($reviews_total === 0): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-gray-500">No reviews found.</td>
                    </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if ($reviews_total_pages > 1): ?>
            <div class="mt-8 flex justify-center gap-3 text-sm font-medium">
              <?php
              $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
              $query = $_GET;
              $query['section'] = 'reviews';
              // Remove filter_stars from query for pagination links if it's "All Stars"
              if (isset($query['filter_stars']) && $query['filter_stars'] == 0) {
                  unset($query['filter_stars']);
              }
              ?>

              <?php if ($reviews_page > 1): ?>
                <?php $query['reviews_page'] = $reviews_page - 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-1">
                  <i class="fas fa-chevron-left"></i> Prev
                </a>
              <?php endif; ?>

              <?php for ($p = 1; $p <= $reviews_total_pages; $p++): ?>
                <?php $query['reviews_page'] = $p; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 rounded-lg transition-colors <?= $p == $reviews_page ? 'bg-admin-primary text-white shadow-md' : 'bg-gray-100 hover:bg-gray-200' ?>">
                  <?= $p ?>
                </a>
              <?php endfor; ?>

              <?php if ($reviews_page < $reviews_total_pages): ?>
                <?php $query['reviews_page'] = $reviews_page + 1; ?>
                <a href="<?= $baseUrl . '?' . http_build_query($query) ?>"
                  class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-1">
                  Next <i class="fas fa-chevron-right"></i>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

    </main>
  </div>

  <div id="editModal" class="fixed inset-0 modal-backdrop hidden items-center justify-center z-50 p-4">
    <div class="modal-content w-full max-w-md p-8">
      <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">Edit Product</h3>
      <form id="editForm" method="POST" action="update_product.php" enctype="multipart/form-data">
        <input type="hidden" name="return_category" id="edit-return-category">
        <input type="hidden" name="id" id="edit-id">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Name:</label>
          <input type="text" name="name" id="edit-name" class="w-full" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Category:</label>
          <input type="text" name="category" id="edit-category" class="w-full" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description:</label>
          <textarea name="description" id="edit-description" class="w-full" rows="3" required></textarea>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Price:</label>
          <input type="number" name="price" id="edit-price" class="w-full" step="0.01" min="0" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Stock:</label>
          <input type="number" name="stock" id="edit-stock" class="w-full" min="0" required>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Image:</label>
            <img id="edit-current-image" src="" alt="Product Image" class="w-24 h-24 object-cover rounded-md mb-2 border border-gray-200">
            <label for="edit-image-file" class="block text-sm font-medium text-gray-700 mb-1">Change Image (optional):</label>
            <input type="file" name="image" id="edit-image-file" accept="image/*" class="w-full">
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeEditModal()" class="modal-button-secondary">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="submit" class="modal-button-primary">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="deleteModal" class="fixed inset-0 modal-backdrop hidden items-center justify-center z-50 p-4">
    <div class="modal-content w-full max-w-sm p-8 text-center">
      <h3 class="text-2xl font-bold text-red-600 mb-4 border-b pb-3"><i class="fas fa-exclamation-triangle mr-2"></i> Delete Product</h3>
      <p id="delete-message" class="text-gray-700 text-lg mb-6"></p>
      <form method="POST" action="delete_product.php">
        <input type="hidden" name="id" id="delete-id">
        <input type="hidden" name="return_category" id="delete-return-category">
        <div class="flex justify-center gap-3 mt-6">
          <button type="button" onclick="closeDeleteModal()" class="modal-button-secondary">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="submit" class="modal-button-danger">
            <i class="fas fa-trash-alt"></i> Delete
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="addModal" class="fixed inset-0 modal-backdrop hidden items-center justify-center z-50 p-4">
    <div class="modal-content w-full max-w-md p-8"> <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">Add New Product</h3>
      <form method="POST" action="add_product.php" enctype="multipart/form-data">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Name:</label>
          <input type="text" name="name" class="w-full" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Category:</label>
          <select name="category" id="add-category-select" class="w-full" required>
            <option value="">-- Select Existing Category --</option>
            <?php foreach ($all_categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
            <option value="new_category_option">-- Add New Category --</option>
          </select>
        </div>
        <div class="mb-4" id="new-category-field" style="display: none;">
          <label class="block text-sm font-medium text-gray-700 mb-1">New Category Name:</label>
          <input type="text" name="new_category" id="add-new-category-input" class="w-full">
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Gift Category:</label>
          <select name="gift_category" class="w-full" required>
            <option value="">-- Select Gift Category --</option>
            <?php foreach ($gift_categories_list as $g_cat): ?>
              <option value="<?= htmlspecialchars($g_cat) ?>"><?= htmlspecialchars($g_cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description:</label>
          <textarea name="description" class="w-full" rows="3" required></textarea>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Price:</label>
          <input type="number" name="price" class="w-full" step="0.01" min="0" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Stock:</label>
          <input type="number" name="stock" class="w-full" min="0" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Image:</label>
          <input type="file" name="image" accept="image/*" class="w-full" required>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeAddModal()" class="modal-button-secondary">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="submit" class="modal-button-primary">
            <i class="fas fa-plus-circle"></i> Add Product
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="viewOrderModal" class="fixed inset-0 modal-backdrop hidden justify-center items-center z-50 p-4">
    <div class="modal-content w-full max-w-lg p-8">
      <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">Order Details</h3>
      <div id="order-details-content" class="text-base text-gray-700 overflow-y-auto max-h-96">
        Loading...
      </div>
      <div class="mt-6 text-right">
        <button onclick="closeOrderDetailsModal()" class="modal-button-secondary">Close</button>
      </div>
    </div>
  </div>

  <div id="updateStatusModal" class="fixed inset-0 modal-backdrop hidden justify-center items-center z-50 p-4">
    <div class="modal-content w-full max-w-sm p-8">
      <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">Update Order Status</h3>
      <form id="status-form">
        <input type="hidden" id="update-order-id" name="order_id">
        <label class="block text-sm font-medium text-gray-700 mb-1">New Status:</label>
        <select id="update-order-status" name="order_status" class="w-full mb-6">
          <option value="processing">Processing</option>
          <option value="validated">Validated</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
          <option value="returned">Returned</option>
        </select>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeUpdateStatusModal()" class="modal-button-secondary">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="button" onclick="submitStatusUpdate()"
            class="modal-button-primary">
            <i class="fas fa-sync-alt"></i> Update Status
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="adminEditProfileModal" class="fixed inset-0 modal-backdrop hidden items-center justify-center z-50 p-4">
        <div class="modal-content w-full max-w-md p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">Edit Admin Profile</h3>
            <form id="adminEditProfileForm">
                <div class="mb-4">
                    <label for="admin-edit-username" class="block text-sm font-medium text-gray-700 mb-1">Username:</label>
                    <input type="text" id="admin-edit-username" name="username" class="w-full" required>
                </div>
                <div class="mb-4">
                    <label for="admin-edit-fname" class="block text-sm font-medium text-gray-700 mb-1">First Name:</label>
                    <input type="text" id="admin-edit-fname" name="fname" class="w-full">
                </div>
                <div class="mb-4">
                    <label for="admin-edit-lname" class="block text-sm font-medium text-gray-700 mb-1">Last Name:</label>
                    <input type="text" id="admin-edit-lname" name="lname" class="w-full">
                </div>
                <div class="mb-4">
                    <label for="admin-edit-phone" class="block text-sm font-medium text-gray-700 mb-1">Phone:</label>
                    <input type="text" id="admin-edit-phone" name="phone" class="w-full">
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeAdminEditProfileModal()" class="modal-button-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="modal-button-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="adminChangeEmailModal" class="fixed inset-0 modal-backdrop hidden items-center justify-center z-50 p-4">
        <div class="modal-content w-full max-w-md p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">Change Admin Email</h3>
            <form id="adminChangeEmailForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Email:</label>
                    <input type="email" id="admin-change-email-current" class="w-full bg-gray-100" readonly>
                </div>
                <div class="mb-4">
                    <label for="admin-change-email-new" class="block text-sm font-medium text-gray-700 mb-1">New Email:</label>
                    <input type="email" id="admin-change-email-new" name="new_email" class="w-full" required>
                </div>
                <div class="mb-4">
                    <label for="admin-email-otp" class="block text-sm font-medium text-gray-700 mb-1">OTP (Sent to Current Email):</label>
                    <div class="flex gap-2">
                        <input type="text" id="admin-email-otp" name="otp" class="w-full" required>
                        <button type="button" id="sendAdminEmailOtpButton" onclick="sendAdminEmailOtp()" class="modal-button-secondary px-4 py-2 text-sm whitespace-nowrap">Send OTP</button>
                    </div>
                    <div id="adminEmailOtpMessage" class="text-sm mt-1"></div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeAdminChangeEmailModal()" class="modal-button-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="modal-button-primary">
                        <i class="fas fa-envelope-open-text"></i> Verify & Change Email
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="adminChangePasswordModal" class="fixed inset-0 modal-backdrop hidden items-center justify-center z-50 p-4">
        <div class="modal-content w-full max-w-md p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">Change Admin Password</h3>
            <form id="adminChangePasswordForm">
                 <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email:</label>
                    <input type="email" id="admin-change-password-email" name="email" class="w-full bg-gray-100" readonly>
                </div>
                <div class="mb-4">
                    <label for="admin-new-password" class="block text-sm font-medium text-gray-700 mb-1">New Password:</label>
                    <input type="password" id="admin-new-password" name="new_password" class="w-full" required>
                </div>
                <div class="mb-4">
                    <label for="admin-confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password:</label>
                    <input type="password" id="admin-confirm-password" name="confirm_password" class="w-full" required>
                </div>
                <div class="mb-4">
                    <label for="admin-password-otp" class="block text-sm font-medium text-gray-700 mb-1">OTP (Sent to Email):</label>
                     <div class="flex gap-2">
                        <input type="text" id="admin-password-otp" name="otp" class="w-full" required>
                        <button type="button" id="sendAdminPasswordOtpButton" onclick="sendAdminPasswordOtp()" class="modal-button-secondary px-4 py-2 text-sm whitespace-nowrap">Send OTP</button>
                    </div>
                    <div id="adminPasswordOtpMessage" class="text-sm mt-1"></div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeAdminChangePasswordModal()" class="modal-button-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="modal-button-primary">
                        <i class="fas fa-key"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="messageBoxContainer"></div>
    <script>
        function showMessage(message, type) {
            const container = document.getElementById('messageBoxContainer');
            const boxId = 'msg-' + Date.now();
            const icon = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
            const boxHtml = `
                <div id="${boxId}" class="message-box ${type}">
                    ${icon}
                    <span>${message}</span>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', boxHtml);
            const boxElement = document.getElementById(boxId);

            // Trigger reflow to enable transition
            boxElement.offsetHeight;

            boxElement.classList.add('show');

            setTimeout(() => {
                boxElement.classList.remove('show');
                setTimeout(() => {
                    boxElement.remove();
                }, 500); // Match transition duration
            }, 3000); // Display duration
        }
    </script>

</body>

</html>
