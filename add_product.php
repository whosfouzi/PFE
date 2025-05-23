<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    // --- START: Updated Category Logic ---
    $category = '';
    if (isset($_POST['category']) && $_POST['category'] === 'new_category_option') {
        // If "Add New Category" was selected, use the value from the new_category input
        if (isset($_POST['new_category']) && !empty(trim($_POST['new_category']))) {
            $category = trim($_POST['new_category']);
        } else {
            // Handle error if new category name is empty when "Add New Category" is selected
            die("New category name is required when 'Add New Category' is selected.");
        }
    } else if (isset($_POST['category']) && !empty(trim($_POST['category']))) {
        // Otherwise, use the selected existing category
        $category = trim($_POST['category']);
    } else {
        // Handle error if no category is selected or entered
        die("Product category is required.");
    }
    // --- END: Updated Category Logic ---

    // --- START: Updated Gift Category Logic ---
    $gift_category = '';
    if (isset($_POST['gift_category']) && !empty(trim($_POST['gift_category']))) {
        $gift_category = trim($_POST['gift_category']);
    } else {
        // Handle error if no gift category is selected
        die("Gift category is required.");
    }
    // --- END: Updated Gift Category Logic ---

    $description = trim($_POST['description']);

    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = basename($_FILES['image']['name']);
        $tmpName = $_FILES['image']['tmp_name'];
        $uploadDir = 'uploads/';
        $targetPath = $uploadDir . $image;

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!move_uploaded_file($tmpName, $targetPath)) {
            die("Image upload failed.");
        }
    } else {
        die("No image uploaded or upload error.");
    }

    $conn = new mysqli("localhost", "root", "", "giftstore");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO products (name, category, description, price, stock, image, gift_category) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssdiss", $name, $category, $description, $price, $stock, $image, $gift_category);
    if ($stmt->execute()) {
        header("Location: admin.php?category=" . urlencode($category) . "&added=1");
        exit();
    } else {
        echo "Insert failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
// The print_r($_FILES) and exit() lines below are for debugging and should be removed in production
// echo "<pre>";
// print_r($_FILES);
// echo "</pre>";
// exit();

?>
