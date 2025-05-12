<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  die("Unauthorized access.");
}

$db = new mysqli("localhost", "root", "", "giftstore");

if ($db->connect_error) {
  die("Database connection failed: " . $db->connect_error);
}

// Get and sanitize input
$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$role = 'delivery person';

// Simple validation
if (!$fname || !$lname || !$username || !$email || !$phone || !$password) {
  die("All fields are required.");
}

// Check if username or email already exists
$check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  die("Username or email already in use.");
}
$check->close();

// Hash password
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$stmt = $db->prepare("INSERT INTO users (fname, lname, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $fname, $lname, $username, $email, $phone, $hashed, $role);

if ($stmt->execute()) {
  header("Location: admin.php?added=1");
  exit();
} else {
  echo "Error: " . $stmt->error;
}
?>
