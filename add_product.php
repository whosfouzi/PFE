<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
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

    $stmt = $conn->prepare("INSERT INTO products (name, category, price, stock, image) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssdis", $name, $category, $price, $stock, $image);
    if ($stmt->execute()) {
        header("Location: admin.php?category=" . urlencode($category) . "&added=1");
        exit();
    } else {
        echo "Insert failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
echo "<pre>";
print_r($_FILES);
echo "</pre>";
exit();

?>
