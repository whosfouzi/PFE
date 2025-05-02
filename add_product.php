<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    // Auto-map category to gift_category
    $giftMap = [
        'Watches'     => 'Gifts for Him',
        'Wallets'     => 'Gifts for Him',
        'Jewellery'   => 'Gifts for Her',
        'Kids'        => 'Gifts for Kids',
        'PhoneCase'   => 'Tech Gifts',
        'Home Decor'  => 'Home & Decor',
        'Crockery'    => 'Home & Decor',
        'Soft Toys'   => 'Gifts for Her'
    ];
    $gift_category = $giftMap[$category] ?? 'General';

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
echo "<pre>";
print_r($_FILES);
echo "</pre>";
exit();

?>
