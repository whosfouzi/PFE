<style>
  body {
    font-family: 'Poppins', sans-serif;
  }
</style>
<!-- WORKING NAVBAR WITH MATERIALIZE 1.0.0 -->

<!-- MATERIALIZE CSS & ICONS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">

<!-- NAVBAR -->
<div class="navbar-fixed">
  <nav class="pink lighten-5">
    <div class="nav-wrapper container">
      <a href="index.php" class="brand-logo" style="display: flex; align-items: center; height: 100%;">
        <span style="color: #56c8d8; font-family: 'Dancing Script', cursive; font-size: 2.3rem;">Sefar</span>
        <span style="color: #c0392b; font-family: 'Dancing Script', cursive; font-size: 2.3rem;">Gifts</span>
      </a>

      <a href="#" data-target="mobile-demo" class="sidenav-trigger right"><i
          class="material-icons pink-text text-darken-2">menu</i></a>

      <ul class="right hide-on-med-and-down">
        <li><a href="index.php" class="pink-text text-darken-2">Home</a></li>
        <li><a href="products.php" class="pink-text text-darken-2">Products</a></li>
        <li><a href="aboutus.php" class="pink-text text-darken-2">About Us</a></li>
        <li><a href="contactus.php" class="pink-text text-darken-2">Contact Us</a></li>
        <?php if (isset($_SESSION['userid'])): ?>
          <li><a class="dropdown-trigger pink-text text-darken-2" href="#!"
              data-target="dropdown2"><?php echo $_SESSION['userid']; ?><i
                class="material-icons right">arrow_drop_down</i></a></li>
        <?php else: ?>
          <li><a href="login.php" class="pink-text text-darken-2">Login</a></li>
        <?php endif; ?>
        <li><a href="shopping_cart.php"><i class="material-icons pink-text text-darken-2">shopping_cart</i></a></li>
      </ul>
    </div>
  </nav>
</div>

<!-- DROPDOWN MENUS -->
<ul id="dropdown2" class="dropdown-content">
  <li><a href="myorders.php">My Orders</a></li>
  <li><a href="logout.php">Logout</a></li>
</ul>

<!-- MOBILE NAV -->
<ul class="sidenav" id="mobile-demo">
  <li><a href="index.php">Home</a></li>
  <li><a href="products.php">Products</a></li>
  <li><a href="aboutus.php">About Us</a></li>
  <li><a href="contactus.php">Contact Us</a></li>
  <?php if (!isset($_SESSION['userid'])): ?>
    <li><a href="signup.php">Sign Up</a></li>
  <?php endif; ?>
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
    M.Dropdown.init(dropdowns, { coverTrigger: false, constrainWidth: false });

    var sidenavs = document.querySelectorAll('.sidenav');
    M.Sidenav.init(sidenavs);
  });
</script>