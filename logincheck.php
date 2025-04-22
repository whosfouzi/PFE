<?php
$unerror = $passerror = "";

if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    $db = new mysqli("localhost", "root", "", "giftstore");

    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }

    $stmt = $db->prepare("SELECT id, fname, lname, username, email, password, role FROM users WHERE username = ?");
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
            session_start();
            $_SESSION['userid'] = $row['fname'] . ' ' . $row['lname'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['id'] = $row['id'];
            $_SESSION['role'] = $row['role'];

            if (empty($_SESSION['shopping_cart'])) {
                $_SESSION['shopping_cart'] = array();
            }

            switch ($row['role']) {
                case 'admin':
                    header("Location: admin.php");
                    break;
                case 'client':
                    header("Location: index.php");
                    break;
                case 'delivery person':
                    header("Location: delivery_dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        }
    }

    $stmt->close();
    $db->close();
}
?>
