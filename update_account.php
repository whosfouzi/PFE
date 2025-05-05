<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$new_username = trim($_POST['username']);
$new_fname = trim($_POST['first_name']);
$new_lname = trim($_POST['last_name']);
$new_phone = trim($_POST['phone_number']);
$new_email = trim($_POST['email']);

$conn = new mysqli("localhost", "root", "", "giftstore");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the username is taken by another user
$check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$check_stmt->bind_param("si", $new_username, $user_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->close();
    header("Location: my_account.php?error=username_taken");
    exit();
}
$check_stmt->close();

// Update user info
$stmt = $conn->prepare("UPDATE users SET username = ?, fname = ?, lname = ?, phone = ?, email = ? WHERE id = ?");
$stmt->bind_param("sssisi", $new_username, $new_fname, $new_lname, $new_phone, $new_email, $user_id);

if ($stmt->execute()) {
    $_SESSION['username'] = $new_username;
    header("Location: my_account.php?updated=1");
    exit();
} else {
    echo "Update failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
