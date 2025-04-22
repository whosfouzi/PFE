<?php 
session_start();
$uerror = $emailerror = "";

$flag = 0;

if (isset($_POST['submit'])) {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $db = new mysqli("localhost", "root", "", "giftstore");

    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }

    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $uerror = "*Username already taken";
  } else {
      // Now check email
      $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->store_result();
  
      if ($stmt->num_rows > 0) {
          $emailerror = "*Email already in use";
      } else {
          $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  
          $stmt = $db->prepare("INSERT INTO users (fname, lname, phone, email, username, password, role) VALUES (?, ?, ?, ?, ?, ?, 'client')");
          $stmt->bind_param("ssssss", $fname, $lname, $phone, $email, $username, $hashed_password);
  
          if ($stmt->execute()) {
              $flag = 1;
              $uerror = "";
          } else {
              $uerror = "*Something went wrong during registration.";
          }
      }
  }
  

    $stmt->close();
    $db->close();
}
?>

<!-- Registration Form -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #fff0f5;
      font-family: 'Poppins', sans-serif;
    }
    .card-panel {
      border-radius: 12px;
      margin-top: 40px;
    }
    .btn-pink {
      background-color: #d81b60;
      border-radius: 8px;
    }
    .btn-pink:hover {
      background-color: #c2185b;
    }
    .error-text {
      color: red;
      font-size: 0.85rem;
      margin-top: -10px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="row">
      <div class="col s12 m10 offset-m1 l8 offset-l2">
        <div class="card-panel white z-depth-2">
          <h5 class="center-align pink-text text-darken-2">Create Your Account</h5>
          <form method="POST" action="signup.php">
            <div class="row">
              <div class="input-field col s6">
                <input type="text" name="fname" required>
                <label for="fname">First Name</label>
              </div>
              <div class="input-field col s6">
                <input type="text" name="lname" required>
                <label for="lname">Last Name</label>
              </div>
            </div>
            <div class="row">
              <div class="input-field col s6">
                <input type="tel" name="phone" required>
                <label for="phone">Phone</label>
              </div>
              <div class="input-field col s6">
                <input type="email" name="email" required>
                <label for="email">Email</label>
                <span class="error-text"><?php echo $emailerror; ?></span>
              </div>
            </div>
            <div class="row">
              <div class="input-field col s6">
                <input type="text" name="username" required>
                <label for="username">Username</label>
                <span class="error-text"><?php echo $uerror; ?></span>
              </div>
              <div class="input-field col s6">
                <input type="password" name="password" required>
                <label for="password">Password</label>
              </div>
            </div>
            <div class="row center-align">
              <button type="submit" name="submit" class="btn btn-pink waves-effect waves-light">Sign Up</button>
            </div>
            <div class="row center-align">
              <p>Already have an account? <a href="login.php" class="pink-text">Login</a></p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
