<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $returnCategory = urlencode($_POST['return_category'] ?? '');

    $db = new mysqli("localhost", "root", "", "giftstore");
    if ($db->connect_error) {
        die("Database connection error: " . $db->connect_error);
    }

    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $db->close();

    header("Location: admin.php?category=$returnCategory&deleted=1");
    exit();
}
?>
