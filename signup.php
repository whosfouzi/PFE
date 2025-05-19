<?php
session_start();
require __DIR__ . '/vendor/autoload.php'; // Ensure this path is correct

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$uerror = $emailerror = $general_error = ""; // Added general_error for other issues
$flag = 0; // Flag doesn't seem strictly necessary here based on logic


if (isset($_POST['submit'])) {
    $fname = trim($_POST['fname'] ?? ''); // Use ?? '' for safety
    $lname = trim($_POST['lname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // Password doesn't need trim

    // Basic server-side validation for required fields
    if (empty($fname) || empty($lname) || empty($phone) || empty($email) || empty($username) || empty($password)) {
        $general_error = "All fields are required.";
    } else {
        $db = new mysqli("localhost", "root", "", "giftstore");

        if ($db->connect_error) {
            error_log("Database Connection failed: " . $db->connect_error);
            $general_error = "Database connection failed. Please try again later.";
        } else {
            // Check username
            $stmt_u = $db->prepare("SELECT id FROM users WHERE username = ?");
            if (!$stmt_u) {
                 error_log("Signup Prepare statement failed (username check): " . $db->error);
                 $general_error = "An error occurred. Please try again.";
            } else {
                $stmt_u->bind_param("s", $username);
                $stmt_u->execute();
                $stmt_u->store_result();

                if ($stmt_u->num_rows > 0) {
                    $uerror = "*Username already taken";
                }
                $stmt_u->close();
            }


            // Check email (only if username is not taken and no general error yet)
            if (empty($uerror) && empty($general_error)) {
                 $stmt_e = $db->prepare("SELECT id FROM users WHERE email = ?");
                 if (!$stmt_e) {
                     error_log("Signup Prepare statement failed (email check): " . $db->error);
                     $general_error = "An error occurred. Please try again.";
                 } else {
                    $stmt_e->bind_param("s", $email);
                    $stmt_e->execute();
                    $stmt_e->store_result();

                    if ($stmt_e->num_rows > 0) {
                        $emailerror = "*Email already in use";
                    }
                    $stmt_e->close();
                 }
            }


            // If no username or email errors, proceed with OTP
            if (empty($uerror) && empty($emailerror) && empty($general_error)) {
                // Store data in session for later insertion after verification
                $_SESSION['pending_signup'] = [
                    'fname' => $fname,
                    'lname' => $lname,
                    'phone' => $phone,
                    'email' => $email,
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT) // Hash the password
                ];

                // Generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['signup_otp'] = $otp;
                $_SESSION['signup_otp_expires'] = time() + 300; // OTP valid for 5 minutes

                // Send OTP via email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    // Server settings (ensure these are correct for your SMTP provider)
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; // e.g., 'smtp.gmail.com' for Gmail
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'fouzi.slimani75@gmail.com'; // Your SMTP username (e.g., your Gmail address)
                    $mail->Password   = 'eygcaowcedzvbyzk';   // Your SMTP password or App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use ENCRYPTION_SMTPS for port 465
                    $mail->Port       = 587; // TCP port to connect to; use 465 for `SMTPS`

                    // Recipients
                    $mail->setFrom('fouzi.slimani75@gmail.com', 'SefarGifts'); // Your sender email and name
                    $mail->addAddress($email, $fname . ' ' . $lname);     // Add recipient

                    // Content
                    $mail->isHTML(true); // Set email format to HTML
                    $mail->Subject = 'Verify Your Email - SefarGifts';
                    $mail->Body    = '<p>Hello ' . htmlspecialchars($fname) . ',</p><p>Thank you for signing up with SefarGifts!</p><p>Your email verification code is: <strong>' . $otp . '</strong></p><p>This code will expire in 5 minutes.</p><p>If you did not attempt to sign up, please ignore this email.</p>';
                    $mail->AltBody = 'Your email verification code for SefarGifts is: ' . $otp . '. This code will expire in 5 minutes.'; // Plain text for non-HTML mail clients

                    $mail->send();
                    // Redirect to OTP verification page
                    header("Location: verify_signup.php");
                    exit(); // Stop script execution after redirection

                } catch (Exception $e) {
                    // Log the PHPMailer error
                    error_log("PHPMailer Error: {$mail->ErrorInfo}");
                    $general_error = "Failed to send verification email. Please check your email address and try again.";
                }
            }
            $db->close(); // Close DB connection if it was opened
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - GiftStore</title>
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
      <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Create Your Account</h2>
      <p class="text-center text-gray-600 mb-6">Join us to start sending and receiving amazing gifts!</p>

       <?php if (!empty($general_error)): ?>
            <div class="mb-6 p-4 rounded-lg shadow-md bg-red-500 text-white">
                <?= htmlspecialchars($general_error) ?>
            </div>
        <?php endif; ?>

      <form method="POST" action="signup.php" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="fname" class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
                <input type="text" id="fname" name="fname" value="<?= htmlspecialchars($_POST['fname'] ?? '') ?>" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary">
            </div>
             <div>
                <label for="lname" class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
                <input type="text" id="lname" name="lname" value="<?= htmlspecialchars($_POST['lname'] ?? '') ?>" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary">
            </div>
        </div>

         <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary">
            </div>
             <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary">
                 <?php if (!empty($emailerror)): ?>
                    <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($emailerror) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <label for="username" class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary">
             <?php if (!empty($uerror)): ?>
                <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($uerror) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
            <input type="password" id="password" name="password" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-turquoise-primary focus:border-turquoise-primary">
             </div>

        <div class="text-center">
          <button type="submit" name="submit"
            class="w-full bg-turquoise-primary hover:bg-cyan-700 text-white px-6 py-3 rounded-lg text-lg font-semibold transition-colors shadow-md">
            Sign Up
          </button>
        </div>
      </form>

      <div class="text-center mt-6">
        <p class="text-gray-600">Already have an account? <a href="login.php" class="text-turquoise-primary hover:underline font-semibold">Login</a></p>
      </div>
    </div>
  </div>

  <?php include("footer.php"); ?>

   <script>
        // Optional: Auto-dismiss general error message after a few seconds
        document.addEventListener('DOMContentLoaded', function() {
            const generalErrorMessage = document.querySelector('.bg-red-500.text-white');
            if (generalErrorMessage) {
                setTimeout(() => {
                    generalErrorMessage.style.display = 'none';
                }, 5000); // Hide after 5 seconds
            }
        });
    </script>

</body>
</html>
