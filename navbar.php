<?php
// Ensure session is started if it's not already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection for fetching categories
$conn = new mysqli("localhost", "root", "", "giftstore");
$gift_categories = [];
if ($conn->connect_error) {
    error_log("Database Connection failed in navbar.php: " . $conn->connect_error);
    // In a production environment, you might want a more robust error page or message
} else {
    $conn->set_charset("utf8mb4");
    $cat_stmt = $conn->prepare("SELECT DISTINCT gift_category FROM products WHERE gift_category IS NOT NULL AND gift_category != ''");
    if ($cat_stmt) {
        $cat_stmt->execute();
        $result = $cat_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $gift_categories[] = $row['gift_category'];
        }
        $cat_stmt->close();
    } else {
        error_log("Navbar Categories Prepare failed: " . $conn->error);
    }
    // The main script (e.g., products.php) should manage its own database connection.
    // This script only needs to read categories and should not close the connection
    // that other parts of the page might still need.
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SefarGifts Navbar</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Custom colors for consistency */
    .text-turquoise-primary { color: #56c8d8; }
    .bg-turquoise-primary { background-color: #56c8d8; }
    .border-turquoise-primary { border-color: #56c8d8; }
    .hover\:bg-cyan-700:hover { background-color: #45b1c0; } /* A darker turquoise */
    .text-red-400 { color: #f87171; } /* Lighter red for logo */

    /* Global font for the body, if not already set in index.php */
    body {
      font-family: 'Poppins', sans-serif;
    }

    /* Navbar specific styles */
    .navbar-modern {
      background-color: #ffffff; /* Solid white, very clean */
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.04); /* Very subtle shadow */
      backdrop-filter: blur(8px); /* Frosted glass effect */
      -webkit-backdrop-filter: blur(8px);
      background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent white */
    }

    /* Logo specific styles */
    .logo-text {
      font-family: 'Dancing Script', cursive;
      font-weight: 700;
      font-size: 2.5rem;
      line-height: 1;
      letter-spacing: -0.05em; /* Tighter letter spacing for modern feel */
      transition: transform 0.2s ease;
    }
    .logo-text:hover {
        transform: scale(1.02); /* Slight scale on hover */
    }

    /* Navigation links */
    .nav-link {
        position: relative;
        color: #4a5568; /* Dark gray for subtle contrast */
        font-weight: 500;
        transition: color 0.2s ease, background-color 0.2s ease;
        padding: 0.5rem 0.9rem; /* Slightly more padding */
        border-radius: 0.5rem; /* Softly rounded corners */
        display: flex;
        align-items: center;
        gap: 0.5rem; /* Space between icon and text */
    }
    .nav-link:hover {
        color: #56c8d8; /* Turquoise hover */
        background-color: #e0f7fa; /* Very light turquoise background */
    }
    .nav-link .fas {
        font-size: 1rem; /* Standard icon size */
        transition: color 0.2s ease;
    }
    .nav-link:hover .fas {
        color: #56c8d8;
    }

    /* Dropdown menu styles */
    .dropdown-menu {
      display: none;
      position: absolute;
      background-color: #ffffff;
      min-width: 180px;
      box-shadow: 0px 6px 15px 0px rgba(0,0,0,0.08); /* Lighter, more modern shadow */
      z-index: 50;
      border-radius: 0.75rem;
      overflow: hidden;
      margin-top: 0.6rem; /* Reduced margin-top */
      opacity: 0;
      transform: translateY(5px); /* Start slightly below */
      transition: opacity 0.2s ease-out, transform 0.2s ease-out; /* Faster, smoother */
      transform-origin: top center;
      border: 1px solid #e2e8f0; /* Subtle border */
    }

    .dropdown-menu.show {
      display: block;
      opacity: 1;
      transform: translateY(0);
    }

    .dropdown-menu a {
      color: #4a5568;
      padding: 0.75rem 1.25rem;
      text-decoration: none;
      display: block;
      transition: background-color 0.2s ease, color 0.2s ease;
      font-weight: 400;
    }

    .dropdown-menu a:hover {
      background-color: #f0f9ff; /* Lightest blue for hover */
      color: #56c8d8;
    }

    /* Hamburger menu icon animation */
    .hamburger-icon {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      width: 26px; /* Slightly smaller */
      height: 18px; /* Slightly smaller */
      cursor: pointer;
      z-index: 60;
      padding: 2px; /* Add padding for better touch target */
    }
    .hamburger-icon span {
      display: block;
      width: 100%;
      height: 2.5px; /* Thinner lines */
      background-color: #333;
      border-radius: 1px; /* Sharper ends */
      transition: all 0.3s ease-in-out;
    }
    .hamburger-icon.open span:nth-child(1) {
      transform: translateY(7.5px) rotate(45deg); /* Adjusted for new height */
    }
    .hamburger-icon.open span:nth-child(2) {
      opacity: 0;
    }
    .hamburger-icon.open span:nth-child(3) {
      transform: translateY(-7.5px) rotate(-45deg); /* Adjusted for new height */
    }

    /* Mobile Menu Overlay */
    .mobile-menu-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.4); /* Lighter, more transparent backdrop */
      z-index: 50;
      display: none;
      backdrop-filter: blur(4px); /* Slightly more blur for modern feel */
      -webkit-backdrop-filter: blur(4px);
      transition: opacity 0.3s ease; /* Smooth fade in/out */
    }
    .mobile-menu {
      position: fixed;
      top: 0;
      right: -100%;
      width: 70%; /* Slightly narrower mobile menu */
      max-width: 280px; /* Max width */
      height: 100%;
      background-color: #ffffff; /* Solid white */
      box-shadow: -5px 0 15px rgba(0,0,0,0.15); /* Softer shadow */
      z-index: 55;
      transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1); /* Standard Material Design ease-out */
      padding-top: 4rem; /* Reduced padding-top */
      overflow-y: auto;
    }
    .mobile-menu.open {
      right: 0;
    }
    .mobile-menu-overlay.open {
      display: block;
    }
    .mobile-menu a, .mobile-menu button {
        padding: 0.9rem 1.5rem; /* Adjusted padding */
        font-size: 1rem; /* Slightly smaller font for mobile */
        font-weight: 500;
        color: #4a5568;
        display: block;
        width: 100%;
        text-align: left;
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .mobile-menu a:hover, .mobile-menu button:hover {
        background-color: #f0f9ff;
        color: #56c8d8;
    }
    .mobile-menu .dropdown-menu {
        position: static;
        box-shadow: none;
        border-radius: 0;
        margin-top: 0;
        padding-left: 1.5rem;
        background-color: #f8f8f8;
        border: none; /* No border for nested dropdowns */
    }
  </style>
</head>
<body class="font-sans">

<nav class="navbar-modern py-4 px-6 relative z-40">
  <div class="container mx-auto flex justify-between items-center">
    <a href="index.php" class="flex items-center space-x-2">
      <span class="logo-text text-turquoise-primary">Sefar</span>
      <span class="logo-text text-red-400">Gifts</span>
    </a>

    <div class="hidden md:flex items-center space-x-2 lg:space-x-4">
      <a href="index.php" class="nav-link">Home</a>

      <div class="relative group">
        <button id="categoriesDropdownTrigger" class="nav-link">
          Categories <i class="fas fa-chevron-down ml-1 text-xs transition-transform"></i>
        </button>
        <div id="categoriesDropdown" class="dropdown-menu">
          <?php foreach ($gift_categories as $cat): ?>
            <a href="products.php?gift=<?= urlencode($cat) ?>"><?= htmlspecialchars($cat) ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if (isset($_SESSION['id'])): ?>
        <a href="my_account.php#orders" class="nav-link">Track Order</a>
      <?php endif; ?>

      <div class="relative group">
        <?php if (isset($_SESSION['id'])): ?>
          <button id="accountDropdownTrigger" class="nav-link">
            <i class="fas fa-user-circle text-lg mr-1"></i> Account <i class="fas fa-chevron-down ml-1 text-xs transition-transform"></i>
          </button>
          <div id="accountDropdown" class="dropdown-menu right-0">
            <a href="my_account.php">My Account</a>
            <a href="logout.php">Logout</a>
          </div>
        <?php else: ?>
          <a href="login.php" class="nav-link">Login</a>
        <?php endif; ?>
      </div>

      <a href="shopping_cart.php" class="nav-link">
        <i class="fas fa-shopping-cart text-lg"></i>
      </a>
    </div>

    <div class="md:hidden flex items-center">
      <a href="shopping_cart.php" class="text-gray-600 hover:text-turquoise-primary transition-colors mr-4">
        <i class="fas fa-shopping-cart text-2xl"></i> </a>
      <button id="hamburgerButton" class="hamburger-icon">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </div>
</nav>

<div id="mobileMenuOverlay" class="mobile-menu-overlay">
  <div id="mobileMenu" class="mobile-menu">
    <div class="p-6 flex flex-col space-y-1">
      <a href="index.php">Home</a>

      <div class="relative">
        <button id="mobileCategoriesDropdownTrigger" class="w-full text-left flex justify-between items-center">
          Categories <i class="fas fa-chevron-down text-sm transition-transform"></i>
        </button>
        <div id="mobileCategoriesDropdown" class="dropdown-menu">
          <?php foreach ($gift_categories as $cat): ?>
            <a href="products.php?gift=<?= urlencode($cat) ?>"><?= htmlspecialchars($cat) ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if (isset($_SESSION['id'])): ?>
        <a href="trackorder.php">Track Order</a>

        <div class="relative">
          <button id="mobileAccountDropdownTrigger" class="w-full text-left flex justify-between items-center">
            Account <i class="fas fa-chevron-down text-sm transition-transform"></i>
          </button>
          <div id="mobileAccountDropdown" class="dropdown-menu">
            <a href="my_account.php">My Account</a>
            <a href="logout.php">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="login.php">Login</a>
      <?php endif; ?>
    </div>
  </div>
</div>


<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Desktop Dropdowns
    const categoriesDropdownTrigger = document.getElementById('categoriesDropdownTrigger');
    const categoriesDropdown = document.getElementById('categoriesDropdown');
    const accountDropdownTrigger = document.getElementById('accountDropdownTrigger');
    const accountDropdown = document.getElementById('accountDropdown');

    function toggleDropdown(trigger, menu) {
      const isShown = menu.classList.contains('show');
      // Close all other desktop dropdowns
      document.querySelectorAll('nav .dropdown-menu.show').forEach(openMenu => {
        if (openMenu !== menu) {
          openMenu.classList.remove('show');
          // Find the correct trigger for the open menu and remove its rotation
          const openMenuTrigger = openMenu.previousElementSibling;
          if (openMenuTrigger && openMenuTrigger.querySelector('.fa-chevron-down')) {
              openMenuTrigger.querySelector('.fa-chevron-down').classList.remove('rotate-180');
          }
        }
      });

      if (isShown) {
        menu.classList.remove('show');
        trigger.querySelector('.fa-chevron-down').classList.remove('rotate-180');
      } else {
        menu.classList.add('show');
        trigger.querySelector('.fa-chevron-down').classList.add('rotate-180');
      }
    }

    if (categoriesDropdownTrigger) {
      categoriesDropdownTrigger.addEventListener('click', function() {
        toggleDropdown(this, categoriesDropdown);
      });
    }
    if (accountDropdownTrigger) {
      accountDropdownTrigger.addEventListener('click', function() {
        toggleDropdown(this, accountDropdown);
      });
    }

    // Close desktop dropdowns when clicking outside
    window.addEventListener('click', function(e) {
      if (categoriesDropdown && !categoriesDropdown.contains(e.target) && categoriesDropdownTrigger && !categoriesDropdownTrigger.contains(e.target)) {
        categoriesDropdown.classList.remove('show');
        if (categoriesDropdownTrigger.querySelector('.fa-chevron-down')) {
            categoriesDropdownTrigger.querySelector('.fa-chevron-down').classList.remove('rotate-180');
        }
      }
      if (accountDropdown && !accountDropdown.contains(e.target) && accountDropdownTrigger && !accountDropdownTrigger.contains(e.target)) {
        accountDropdown.classList.remove('show');
        if (accountDropdownTrigger.querySelector('.fa-chevron-down')) {
            accountDropdownTrigger.querySelector('.fa-chevron-down').classList.remove('rotate-180');
        }
      }
    });


    // Mobile Menu
    const hamburgerButton = document.getElementById('hamburgerButton');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');

    function toggleMobileMenu() {
      hamburgerButton.classList.toggle('open');
      mobileMenu.classList.toggle('open');
      mobileMenuOverlay.classList.toggle('open');
      // Close any open mobile dropdowns when closing the main menu
      if (!mobileMenu.classList.contains('open')) {
          document.querySelectorAll('#mobileMenu .dropdown-menu.show').forEach(openMenu => {
              openMenu.classList.remove('show');
              const openMenuTrigger = openMenu.previousElementSibling;
              if (openMenuTrigger && openMenuTrigger.querySelector('.fa-chevron-down')) {
                  openMenuTrigger.querySelector('.fa-chevron-down').classList.remove('rotate-180');
              }
          });
      }
    }

    hamburgerButton.addEventListener('click', toggleMobileMenu);
    mobileMenuOverlay.addEventListener('click', function(e) {
      if (e.target === mobileMenuOverlay) { // Only close if clicking on the backdrop
        toggleMobileMenu();
      }
    });

    // Mobile Dropdowns
    const mobileCategoriesDropdownTrigger = document.getElementById('mobileCategoriesDropdownTrigger');
    const mobileCategoriesDropdown = document.getElementById('mobileCategoriesDropdown');
    const mobileAccountDropdownTrigger = document.getElementById('mobileAccountDropdownTrigger');
    const mobileAccountDropdown = document.getElementById('mobileAccountDropdown');

    function toggleMobileDropdown(trigger, menu) {
      const isShown = menu.classList.contains('show');
      if (isShown) {
        menu.classList.remove('show');
        trigger.querySelector('.fa-chevron-down').classList.remove('rotate-180');
      } else {
        // Close other mobile dropdowns if open
        document.querySelectorAll('#mobileMenu .dropdown-menu.show').forEach(openMenu => {
            if (openMenu !== menu) {
                openMenu.classList.remove('show');
                const openMenuTrigger = openMenu.previousElementSibling;
                if (openMenuTrigger && openMenuTrigger.querySelector('.fa-chevron-down')) { // Added missing check
                  openMenuTrigger.querySelector('.fa-chevron-down').classList.remove('rotate-180');
                }
            }
        });
        menu.classList.add('show');
        trigger.querySelector('.fa-chevron-down').classList.add('rotate-180');
      }
    }

    if (mobileCategoriesDropdownTrigger) {
        mobileCategoriesDropdownTrigger.addEventListener('click', function() {
            toggleMobileDropdown(this, mobileCategoriesDropdown);
        });
    }
    if (mobileAccountDropdownTrigger) {
        mobileAccountDropdownTrigger.addEventListener('click', function() {
            toggleMobileDropdown(this, mobileAccountDropdown);
        });
    }

    // Close mobile menu when a link is clicked (optional, but good UX)
    document.querySelectorAll('#mobileMenu a').forEach(link => {
        link.addEventListener('click', () => {
            // Check if the clicked link is not a dropdown trigger itself
            const parentButton = link.closest('button');
            if (!parentButton || (!parentButton.id.includes('DropdownTrigger') && !parentButton.classList.contains('nav-link'))) {
                if (mobileMenu.classList.contains('open')) {
                    toggleMobileMenu();
                }
            }
        });
    });

  });
</script>

</body>
</html>
