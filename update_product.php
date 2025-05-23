<?php
session_start();

// Ensure only admins can access this script
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $db = new mysqli("localhost", "root", "", "giftstore");
    if ($db->connect_error) {
        // Log the error for debugging, but don't expose sensitive info to the user
        error_log("Database Connection failed in update_product.php: " . $db->connect_error);
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
        exit();
    }
    $db->set_charset("utf8mb4");

    // Sanitize and validate input - Removed FILTER_SANITIZE_STRING as it's deprecated and prepared statements handle escaping.
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW); // Use FILTER_UNSAFE_RAW or similar for strings
    $category = filter_input(INPUT_POST, 'category', FILTER_UNSAFE_RAW);
    $description = filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $return_category = filter_input(INPUT_POST, 'return_category', FILTER_UNSAFE_RAW); // For redirecting back to correct category view

    // Basic validation
    if (!$id || empty($name) || empty($category) || empty($description) || $price === false || $stock === false || $price < 0 || $stock < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or missing product data.']);
        $db->close();
        exit();
    }

    $image_filename = null; // Initialize image filename

    // Check if a new image file was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_type = $_FILES['image']['type'];

        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpeg', 'jpg', 'png', 'gif'];

        if (!in_array($file_ext, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image file type. Only JPG, JPEG, PNG, GIF are allowed.']);
            $db->close();
            exit();
        }

        // Generate a unique filename to prevent overwrites and security issues
        $image_filename = uniqid('product_', true) . '.' . $file_ext;
        $upload_dir = __DIR__ . '/uploads/'; // Absolute path to uploads directory
        $upload_path = $upload_dir . $image_filename;

        // Ensure the uploads directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory with full permissions if it doesn't exist
        }

        // Move the uploaded file
        if (!move_uploaded_file($file_tmp_name, $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload new image.']);
            $db->close();
            exit();
        }

        // If a new image is uploaded, delete the old one (optional but recommended)
        // First, get the old image filename from the database
        $stmt_old_image = $db->prepare("SELECT image FROM products WHERE id = ?");
        $stmt_old_image->bind_param("i", $id);
        $stmt_old_image->execute();
        $stmt_old_image->bind_result($old_image_filename);
        $stmt_old_image->fetch();
        $stmt_old_image->close();

        if ($old_image_filename && file_exists($upload_dir . $old_image_filename) && $old_image_filename !== 'default.jpg') {
            unlink($upload_dir . $old_image_filename); // Delete old image
        }

    } else {
        // No new image uploaded, retrieve the current image filename from the database
        // This is important if only other fields are being updated, but not the image
        $stmt_current_image = $db->prepare("SELECT image FROM products WHERE id = ?");
        $stmt_current_image->bind_param("i", $id);
        $stmt_current_image->execute();
        $stmt_current_image->bind_result($image_filename); // Assigns to $image_filename
        $stmt_current_image->fetch();
        $stmt_current_image->close();

        if (!$image_filename) {
            // This case should ideally not happen if product exists, but as a fallback
            $image_filename = 'default.jpg'; // Or handle as an error if image is mandatory
        }
    }

    // Prepare the SQL update statement
    $sql = "UPDATE products SET name = ?, category = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?";
    $stmt = $db->prepare($sql);

    if ($stmt === false) {
        error_log("Prepare failed in update_product.php: " . $db->error);
        echo json_encode(['success' => false, 'message' => 'Database statement preparation failed.']);
        $db->close();
        exit();
    }

    // Bind parameters - Corrected type string to match all 7 parameters (sssdssi)
    $stmt->bind_param("sssdssi", $name, $category, $description, $price, $stock, $image_filename, $id);

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
    } else {
        error_log("Execute failed in update_product.php: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update product.']);
    }

    $stmt->close();
    $db->close();

} else {
    // Not a POST request
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// Redirect back to admin.php after processing
// This redirect is handled by JavaScript in admin.php after the fetch call.
// The PHP script now only outputs JSON.
?>
