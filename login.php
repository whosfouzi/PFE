<?php
session_start();
$unerror = $passerror = "";

if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    $db = new mysqli("localhost", "root", "", "giftstore");

    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }

    $stmt = $db->prepare("SELECT id, username, email, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $unerror = "*User does not exist";
    } else {
        $row = $result->fetch_assoc();

        if (!password_verify($password, $row['password'])) {
            $passerror = "*Invalid password";
        } else {
            $_SESSION['userid'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['id'] = $row['id'];
            $_SESSION['role'] = $row['role'];

            // Load cart items into session upon login
            $stmt_cart = $db->prepare("
                SELECT p.id as item_id, p.name as item_name, p.price as item_price, ci.quantity as item_quantity, p.category as item_category
                FROM cart_items ci
                JOIN cart c ON ci.cart_id = c.id
                JOIN products p ON ci.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt_cart->bind_param("i", $_SESSION['id']);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();
            $_SESSION["shopping_cart"] = $result_cart->fetch_all(MYSQLI_ASSOC);
            $stmt_cart->close();

            if (!empty($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
            } else {
                switch ($row['role']) {
                    case 'admin':
                        header("Location: admin.php");
                        break;
                    case 'delivery person':
                        header("Location: delivery_dashboard.php");
                        break;
                    default:
                        header("Location: index.php");
                }
            }
            exit();
        }
    }

    $stmt->close();
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login - GiftStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
  <?php include("navbar.php"); ?>
  <div class="container mx-auto px-4 py-10">
    <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
      <h2 class="text-2xl font-bold text-center mb-6">Login</h2>
      <form method="POST" action="">
        <div class="mb-4">
          <label class="block font-semibold">Username</label>
          <input type="text" name="username" class="w-full px-4 py-2 border rounded" required>
          <span class="text-red-500 text-sm"><?= $unerror ?></span>
        </div>
        <div class="mb-4">
          <label class="block font-semibold">Password</label>
          <input type="password" name="pwd" class="w-full px-4 py-2 border rounded" required>
          <span class="text-red-500 text-sm"><?= $passerror ?></span>
        </div>
        <div class="text-center">
          <button type="submit" name="submit"
            class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700">Login</button>
        </div>
      </form>
    </div>
  </div>
  <?php include("footer.php"); ?>
</body>

</html>