<?php
session_start();
$unerror = $passerror = "";

// Check if user is already logged in, redirect if true
if (isset($_SESSION['id'])) {
    // Redirect based on role or to index if no specific role redirect
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit();
}


if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    $db = new mysqli("localhost", "root", "", "giftstore");

    if ($db->connect_error) {
        // Log database connection error
        error_log("Database Connection failed: " . $db->connect_error);
        // Display a user-friendly error message
        $unerror = "*Database connection failed. Please try again later."; // Or a more general error
    } else {

        $stmt = $db->prepare("SELECT id, username, email, password, role FROM users WHERE username = ?");
        if (!$stmt) {
            error_log("Login Prepare statement failed: " . $db->error);
             $unerror = "*An error occurred during login. Please try again.";
        } else {
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
                    // Authentication successful
                    $_SESSION['userid'] = $row['id']; // Keep userid for backward compatibility if needed
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['id'] = $row['id']; // Primary user ID session key
                    $_SESSION['role'] = $row['role'];

                    // Load cart items into session upon login from the database
                    $stmt_cart = $db->prepare("
                        SELECT p.id as item_id, p.name as item_name, p.price as item_price, ci.quantity as item_quantity, p.image as item_image, p.stock as item_stock
                        FROM cart_items ci
                        JOIN cart c ON ci.cart_id = c.id
                        JOIN products p ON ci.product_id = p.id
                        WHERE c.user_id = ?
                    ");
                    if ($stmt_cart) {
                        $stmt_cart->bind_param("i", $_SESSION['id']);
                        $stmt_cart->execute();
                        $result_cart = $stmt_cart->get_result();
                        $_SESSION["shopping_cart"] = $result_cart->fetch_all(MYSQLI_ASSOC);
                        $stmt_cart->close();
                    } else {
                        error_log("Login Cart Load Prepare failed: " . $db->error);
                        // Continue without loading cart if there's an error, user can refresh cart page
                        $_SESSION["shopping_cart"] = [];
                    }


                    // Redirect to the page the user was trying to access before login, or default
                    if (!empty($_SESSION['redirect_after_login'])) {
                        $redirect = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']); // Clear the stored URL
                        header("Location: $redirect");
                    } else {
                        // Default redirect based on role
                        switch ($row['role']) {
                            case 'admin':
                                header("Location: admin.php");
                                break;
                            default:
                                header("Location: index.php");
                        }
                    }
                    exit(); // Stop script execution after redirection
                }
            }
             $stmt->close();
        }
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login - GiftStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
   <style>
        /* Custom styles for the specific turquoise color */
        .text-turquoise-primary {
            color: #56c8d8;
        }
        .bg-turquoise-primary {
            background-color: #56c8d8;
        }
        .border-turquoise-primary {
            border-color: #56c8d8;
        }
        .focus\:ring-turquoise-primary:focus {
            --tw-ring-color: #56c8d8;
        }
         .focus\:border-turquoise-primary:focus {
            border-color: #56c8d8;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">
  <?php include("navbar.php"); ?>

  <div class="flex items-center justify-center min-h-screen py-12 px-4">
    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-xl">
      <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Welcome Back!</h2>
      <p class="text-center text-gray-600 mb-6">Login to access your account and shopping cart.</p>

      <form method="POST" action="login.php" class="space-y-6">
        <div>
          <label for="username" class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
          <input type="text" id="username" name="username" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary">
          <?php if (!empty($unerror)): ?>
            <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($unerror) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="pwd" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
          <input type="password" id="pwd" name="pwd" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary">
           <?php if (!empty($passerror)): ?>
            <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($passerror) ?></p>
          <?php endif; ?>
        </div>

        <div class="text-center">
          <button type="submit" name="submit"
            class="w-full bg-turquoise-primary hover:bg-cyan-700 text-white px-6 py-3 rounded-lg text-lg font-semibold transition-colors shadow-md">
            Login
          </button>
        </div>
      </form>

      <div class="text-center mt-6">
        <p class="text-gray-600">Don't have an account? <a href="signup.php" class="text-turquoise-primary hover:underline font-semibold">Sign Up</a></p>
      </div>
    </div>
  </div>

  <?php include("footer.php"); ?>
</body>

</html>
