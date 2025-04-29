<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SefarGifts Navbar</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f9f9f9;
    }

    nav {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      padding: 0 20px;
    }

    .brand-logo span {
      font-family: 'Dancing Script', cursive;
      font-size: 2.2rem;
      font-weight: 400;
    }

    .nav-item {
      font-weight: 500;
      color: #333 !important;
      padding: 0 12px;
      transition: color 0.3s;
    }

    .nav-item:hover {
      color: #56c8d8 !important;
      background-color: transparent !important;
    }

    nav ul li a.dropdown-trigger,
    nav ul li a.dropdown-trigger:hover {
      background-color: transparent !important;
      box-shadow: none !important;
    }

    nav ul.dropdown-content li a,
nav ul.dropdown-content li a:hover {
  background-color: transparent !important;
  box-shadow: none !important;
  color: #333 !important;
  font-weight: 500;
}

nav ul.dropdown-content li a:hover {
  color: #56c8d8 !important;
}

/* Final missing fix */
.dropdown-content li:hover {
  background-color: transparent !important;
}

    .dropdown-content {
      border-radius: 8px;
      overflow: hidden;
    }

    .search-wrapper {
      max-width: 300px;
      margin: 0 auto;
      position: relative;
      display: flex;
      align-items: center;
      background: #f1f1f1;
      border-radius: 20px;
      padding: 0 10px;
    }

    .search-wrapper input {
      border: none;
      background: transparent;
      padding: 8px 10px;
      outline: none;
      width: 100%;
    }

    .search-wrapper i {
      color: #aaa;
    }
  </style>
</head>
<body>

<?php
// Assuming you already have session_start() and db connection if needed
?>

<div class="navbar">
  <nav class="white lighten-5">
    <div class="nav-wrapper container">
      <a href="index.php" class="brand-logo" style="display: flex; align-items: center;">
        <span style="color: #56c8d8;">Sefar</span>
        <span style="color: rgb(255, 25, 0);">Gifts</span>
      </a>

      <ul class="left hide-on-med-and-down" style="margin-left: 250px;">
        <li><a href="index.php" class="nav-item">Home</a></li>
        <li><a class="dropdown-trigger nav-item" href="#" data-target="categories2">Categories<i class="material-icons right">arrow_drop_down</i></a></li>
        <?php if (isset($_SESSION['userid'])): ?>
          <li><a href="trackorder.php" class="nav-item">Track Order</a></li>
        <?php endif; ?>
      </ul>

      <ul class="right hide-on-med-and-down">
        <?php if (isset($_SESSION['userid'])): ?>
          <li><a class="dropdown-trigger nav-item" href="#" data-target="dropdown2"><i class="material-icons">account_circle</i></a></li>
        <?php else: ?>
          <li><a href="login.php" class="nav-item">Login</a></li>
        <?php endif; ?>
        <li><a href="shopping_cart.php"><i class="material-icons black-text">shopping_cart</i></a></li>
      </ul>

    </div>
  </nav>
</div>

<!-- DROPDOWN MENUS -->
<ul id="dropdown2" class="dropdown-content">
  <li><a href="myorders.php">My Orders</a></li>
  <li><a href="logout.php">Logout</a></li>
</ul>

<ul id="categories2" class="dropdown-content">
  <li><a href="category1.php">Kids</a></li>
  <li><a href="category2.php">PhoneCase</a></li>
  <li><a href="category3.php">Home Decor</a></li>
  <li><a href="category4.php">Watches</a></li>
  <li><a href="category5.php">Jewellery</a></li>
  <li><a href="category6.php">Soft Toys</a></li>
  <li><a href="category7.php">Crockery</a></li>
  <li><a href="category8.php">Wallet</a></li>
</ul>

<!-- MATERIALIZE JS + INIT -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var dropdowns = document.querySelectorAll('.dropdown-trigger');
    M.Dropdown.init(dropdowns, { 
      coverTrigger: false, 
      constrainWidth: false,
      hover: false, 
      inDuration: 300,
      outDuration: 200
    });
  });
</script>

</body>
</html>