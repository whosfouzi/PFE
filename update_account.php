<?php
session_start();
header('Content-Type: application/json'); // Set header for JSON response

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    error_log("Database Connection failed in update_product.php: " . $db->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}
$db->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? ''); // Ensure description is captured
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    if (empty($id) || empty($name) || empty($category) || empty($description) || !isset($price) || !isset($stock)) {
        echo json_encode(['success' => false, 'message' => 'Missing required product data.']);
        $db->close();
        exit();
    }

    // Handle image upload if a new image is provided
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/'; // Make sure this directory exists and is writable
        $image_name = basename($_FILES['image']['name']);
        // Create a unique filename to prevent overwrites
        $image_path = $upload_dir . uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $image_name);

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            error_log("Failed to move uploaded file for product ID: " . $id);
            echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
            $db->close();
            exit();
        }
    }

    // Prepare the SQL statement based on whether an image was uploaded
    if ($image_path) {
        $stmt = $db->prepare("UPDATE products SET name=?, description=?, category=?, price=?, stock=?, image=? WHERE id=?");
        if (!$stmt) {
            error_log("Update product (with image) prepare failed: " . $db->error);
            echo json_encode(['success' => false, 'message' => 'Database error during update preparation.']);
            $db->close();
            exit();
        }
        $stmt->bind_param("sssdssi", $name, $description, $category, $price, $stock, $image_path, $id);
    } else {
        $stmt = $db->prepare("UPDATE products SET name=?, description=?, category=?, price=?, stock=? WHERE id=?");
        if (!$stmt) {
            error_log("Update product (no image) prepare failed: " . $db->error);
            echo json_encode(['success' => false, 'message' => 'Database error during update preparation.']);
            $db->close();
            exit();
        }
        $stmt->bind_param("ssdsii", $name, $description, $category, $price, $stock, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
    } else {
        error_log("Update product execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update product.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$db->close();
?>
