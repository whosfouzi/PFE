<?php
session_start();
if (!isset($_SESSION['pending_signup'], $_SESSION['signup_otp'])) {
    header("Location: signup.php");
    exit();
}

$error = "";
if (isset($_POST['verify'])) {
    $entered_otp = $_POST['otp'];
    if (time() > $_SESSION['signup_otp_expires']) {
        $error = "The OTP has expired. Please register again.";
        session_unset();
        session_destroy();
    } elseif ($entered_otp == $_SESSION['signup_otp']) {
        $user = $_SESSION['pending_signup'];

        $db = new mysqli("localhost", "root", "", "giftstore");
        if ($db->connect_error) {
            die("Connection failed: " . $db->connect_error);
        }

        $stmt = $db->prepare("INSERT INTO users (fname, lname, phone, email, username, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $user['fname'], $user['lname'], $user['phone'], $user['email'], $user['username'], $user['password']);

        if ($stmt->execute()) {
            unset($_SESSION['pending_signup'], $_SESSION['signup_otp'], $_SESSION['signup_otp_expires']);
            header("Location: login.php?verified=true");
            exit();
        } else {
            $error = "Failed to complete registration. Try again.";
        }

        $stmt->close();
        $db->close();
    } else {
        $error = "Incorrect OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Email - SefarGifts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-6 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center mb-4">Verify Your Email</h2>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <input type="text" name="otp" required class="w-full border px-3 py-2 rounded" placeholder="Enter the 6-digit code" />
            <button type="submit" name="verify" class="w-full bg-[#56c8d8] hover:bg-[#45b1c0] text-white py-2 rounded">
                Verify and Complete Signup
            </button>
        </form>
    </div>
</body>
</html>
