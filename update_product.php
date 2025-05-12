<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    $db = new mysqli("localhost", "root", "", "giftstore");

    if ($db->connect_error) {
        die("Database connection error: " . $db->connect_error);
    }

    $stmt = $db->prepare("UPDATE products SET name=?, description=?, category=?, price=?, stock=? WHERE id=?");
    $stmt->bind_param("sssdii", $name, $description, $category, $price, $stock, $id);
    $stmt->execute();
    $stmt->close();
    $db->close();

    // Redirect back to admin with optional message
    $returnCategory = urlencode($_POST['return_category'] ?? '');
    header("Location: admin.php?category=$returnCategory&updated=1");

    exit();
}
?>