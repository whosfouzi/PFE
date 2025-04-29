<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>
  <title>GiftStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- FONTS      -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
  <!-- Font Awesome Bootstrap -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- CSS -->
  <link rel="stylesheet" href="css/go_to.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .fade-in {
      animation: fadeIn 1.2s ease-out forwards;
    }
  </style>
  <link rel="stylesheet" href="css/style1.css">
</head>

<body>
  <!--navigation bar-->
  <?php include('navbar.php'); ?>
  <!--slider-->

  <!-- Hero Section with Full-Height Image in a Box -->
  <section class="bg-gray-100 py-12 fade-in">
    <div class="max-w-7xl mx-auto px-4">
      <div class="bg-white rounded-3xl shadow-lg overflow-hidden md:flex">
        <!-- Text Column -->
        <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center">
          <h1 class="text-4xl font-bold text-gray-900 mb-4">
            Let them <span class="text-red-500" style="font-family: Dancing Script;">choose</span> their favorites
          </h1>
          <p class="text-lg text-gray-700 mb-6">
            Send a sweet gift to friends, colleagues or loved ones through email or text.
          </p>
          <div class="mt-4">
            <a href="products.php"
              class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg shadow w-auto">
              Send a Gift
            </a>
          </div>
        </div>

        <!-- Image Column (covers full height) -->
        <div class="w-full md:w-1/2">
          <img src="images/ChatGPT Image Apr 28, 2025, 09_03_17 PM.png" alt="Gift Bouquet"
            class="h-full w-full object-cover object-center md:rounded-r-3xl" />
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section class="py-16 bg-white text-center">
    <div class="max-w-6xl mx-auto px-4">
      <p class="text-red-600 font-semibold text-sm uppercase mb-2 tracking-wide">Seamless Gifting, Handled with Care</p>
      <h2 class="text-4xl font-bold text-gray-900 mb-6">How It Works</h2>
      <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-12">
        We make thoughtful gifting effortless. From the moment you place your order, our team ensures it’s handled with
        love,
        verified with care, and delivered with precision — every time.
      </p>

      <!-- Use flex instead of grid to keep arrows inline -->
      <div class="hidden md:flex items-start justify-center gap-6">

        <!-- Step 1 -->
        <div class="text-center">
          <img src="images/image1.png" alt="Client Orders" class="rounded-2xl mx-auto mb-4 w-60 h-60 object-cover" />
          <h3 class="text-xl font-semibold text-gray-900 mb-2">You Order</h3>
          <p class="text-gray-600 text-sm max-w-xs mx-auto">Browse handpicked gifts, personalize your choice, and place
            an order in just a few clicks.</p>
        </div>

        <!-- Arrow -->
        <div class="flex items-center justify-center" style="height: 15rem;">
          <img src="images/arrow.png" alt="Arrow" class="w-10 h-auto" />
        </div>

        <!-- Step 2 -->
        <div class="text-center">
          <img src="images/image2.png" alt="Admin Approves" class="rounded-2xl mx-auto mb-4 w-60 h-60 object-cover" />
          <h3 class="text-xl font-semibold text-gray-900 mb-2">We Prepare</h3>
          <p class="text-gray-600 text-sm max-w-xs mx-auto">Our team reviews your order, carefully packs your gift, and
            ensures everything is perfect.</p>
        </div>

        <!-- Arrow -->
        <div class="flex items-center justify-center" style="height: 15rem;">
          <img src="images/arrow.png" alt="Arrow" class="w-10 h-auto" />
        </div>

        <!-- Step 3 -->
        <div class="text-center">
          <img src="images/image.png" alt="Delivery Ships" class="rounded-2xl mx-auto mb-4 w-60 h-60 object-cover" />
          <h3 class="text-xl font-semibold text-gray-900 mb-2">We Deliver</h3>
          <p class="text-gray-600 text-sm max-w-xs mx-auto">Our trusted delivery team brings the surprise right to the
            doorstep.</p>
        </div>
      </div>

      <!-- Mobile fallback: stack vertically without arrows -->
      <div class="flex flex-col gap-12 md:hidden mt-10">
        <!-- Step 1 -->
        <div class="text-center">
          <img src="images/image1.png" alt="Client Orders" class="rounded-2xl mx-auto mb-4 w-60 h-60 object-cover" />
          <h3 class="text-xl font-semibold text-gray-900 mb-2">You Order</h3>
          <p class="text-gray-600 text-sm max-w-xs mx-auto">Browse handpicked gifts, personalize your choice, and place
            an order in just a few clicks.</p>
        </div>

        <!-- Step 2 -->
        <div class="text-center">
          <img src="images/image2.png" alt="Admin Approves" class="rounded-2xl mx-auto mb-4 w-60 h-60 object-cover" />
          <h3 class="text-xl font-semibold text-gray-900 mb-2">We Prepare</h3>
          <p class="text-gray-600 text-sm max-w-xs mx-auto">Our team reviews your order, carefully packs your gift, and
            ensures everything is perfect.</p>
        </div>

        <!-- Step 3 -->
        <div class="text-center">
          <img src="images/image.png" alt="Delivery Ships" class="rounded-2xl mx-auto mb-4 w-60 h-60 object-cover" />
          <h3 class="text-xl font-semibold text-gray-900 mb-2">We Deliver</h3>
          <p class="text-gray-600 text-sm max-w-xs mx-auto">Our trusted delivery team brings the surprise right to the
            doorstep.</p>
        </div>

      </div>
    </div>
  </section>

  <!-- Why Choose Us Section -->
  <section class="bg-gray-100 py-20">
    <div class="max-w-7xl mx-auto px-6">

      <!-- Section Title -->
      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-red-600 mb-4">Why Choose Us</h2>
        <p class="text-gray-600 max-w-2xl mx-auto text-lg">
          At SefarGifts, we deliver more than gifts — we deliver experiences and emotions.
        </p>
      </div>

      <!-- Reasons Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">

        <!-- Reason 1 -->
        <div class="bg-white rounded-2xl p-8 shadow hover:shadow-md transition">
          <img src="https://img.icons8.com/fluency/64/000000/delivery.png" alt="Fast Delivery" class="mx-auto mb-4">
          <h4 class="text-xl font-semibold text-gray-900 mb-2">Fast Delivery</h4>
          <p class="text-gray-600 text-sm">Guaranteed quick shipping so gifts arrive perfectly on time.</p>
        </div>

        <!-- Reason 2 -->
        <div class="bg-white rounded-2xl p-8 shadow hover:shadow-md transition">
          <img src="https://img.icons8.com/fluency/64/000000/lock-2.png" alt="Secure Payment" class="mx-auto mb-4">
          <h4 class="text-xl font-semibold text-gray-900 mb-2">Secure Payment</h4>
          <p class="text-gray-600 text-sm">Your privacy and security are protected with top-level encryption.</p>
        </div>

        <!-- Reason 3 -->
        <div class="bg-white rounded-2xl p-8 shadow hover:shadow-md transition">
          <img src="https://img.icons8.com/fluency/64/000000/gift--v1.png" alt="Handpicked Gifts" class="mx-auto mb-4">
          <h4 class="text-xl font-semibold text-gray-900 mb-2">Handpicked Gifts</h4>
          <p class="text-gray-600 text-sm">Curated collections carefully selected to match every occasion.</p>
        </div>

        <!-- Reason 4 -->
        <div class="bg-white rounded-2xl p-8 shadow hover:shadow-md transition">
          <img src="https://img.icons8.com/fluency/64/000000/customer-support.png" alt="Support" class="mx-auto mb-4">
          <h4 class="text-xl font-semibold text-gray-900 mb-2">Top Support</h4>
          <p class="text-gray-600 text-sm">Our friendly team is always ready to assist you before and after your order.
          </p>
        </div>

      </div>

    </div>
  </section>


  <!-- Featured Categories Section -->
  <section class="bg-gray-100 py-20">
    <div class="max-w-7xl mx-auto px-6">

      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-red-600 mb-4">Featured Gifts</h2>
        <p class="text-gray-600 max-w-2xl mx-auto text-lg">
          Explore our best gift collections for every occasion.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">

        <!-- Category 1 -->
        <div class="bg-white rounded-2xl p-8 shadow hover:shadow-lg transition">
          <img src="https://img.icons8.com/fluency/96/000000/birthday-cake.png" alt="Birthday Gifts"
            class="w-20 h-20 mx-auto mb-6">
          <h4 class="text-xl font-bold text-gray-900 mb-2">Birthday Gifts</h4>
          <p class="text-gray-600 text-sm">Make birthdays unforgettable with our curated surprises.</p>
        </div>

        <!-- Category 2 -->
        <div class="bg-white rounded-2xl p-8 shadow hover:shadow-lg transition">
          <img src="https://img.icons8.com/fluency/96/000000/wedding-gift.png" alt="Anniversary Gifts"
            class="w-20 h-20 mx-auto mb-6">
          <h4 class="text-xl font-bold text-gray-900 mb-2">Anniversary Gifts</h4>
          <p class="text-gray-600 text-sm">Celebrate love and special moments in style.</p>
        </div>

        <!-- Category 3 -->
        <div class="bg-white rounded-2xl p-8 shadow hover:shadow-lg transition">
          <img src="https://img.icons8.com/fluency/96/000000/handshake.png" alt="Corporate Gifts"
            class="w-20 h-20 mx-auto mb-6">
          <h4 class="text-xl font-bold text-gray-900 mb-2">Corporate Gifts</h4>
          <p class="text-gray-600 text-sm">Build relationships with premium and thoughtful corporate gifting.</p>
        </div>

      </div>

    </div>
  </section>


  <!-- Testimonials Section -->
  <section class="bg-white py-20">
    <div class="max-w-7xl mx-auto px-6">

      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-red-600 mb-4">What Our Customers Say</h2>
        <p class="text-gray-600 max-w-2xl mx-auto text-lg">
          Trusted by hundreds of happy customers who made someone's day special.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">

        <!-- Testimonial 1 -->
        <div class="bg-gray-100 rounded-2xl p-8 shadow hover:shadow-md transition">
          <p class="text-gray-700 mb-4">"The easiest and most beautiful way to send gifts. My friends loved it!"</p>
          <h4 class="text-red-600 font-bold">Sarah M.</h4>
        </div>

        <!-- Testimonial 2 -->
        <div class="bg-gray-100 rounded-2xl p-8 shadow hover:shadow-md transition">
          <p class="text-gray-700 mb-4">"Fast delivery, great packaging, and wonderful customer service."</p>
          <h4 class="text-red-600 font-bold">Omar B.</h4>
        </div>

        <!-- Testimonial 3 -->
        <div class="bg-gray-100 rounded-2xl p-8 shadow hover:shadow-md transition">
          <p class="text-gray-700 mb-4">"Such a fun way to let people choose exactly what they want!"</p>
          <h4 class="text-red-600 font-bold">Layla R.</h4>
        </div>

      </div>

    </div>
  </section>

  <!-- Newsletter Signup Section -->
  <section class="bg-white py-20">
    <div class="max-w-3xl mx-auto px-6 text-center">

      <h2 class="text-4xl font-bold text-red-600 mb-4">Stay Updated</h2>
      <p class="text-gray-600 mb-8">
        Subscribe to get exclusive offers, gift ideas, and special promotions.
      </p>

      <form class="flex flex-col md:flex-row items-center gap-4 justify-center">
        <input type="email" placeholder="Enter your email"
          class="w-full md:w-80 px-5 py-3 rounded-full border border-gray-300 focus:ring-2 focus:ring-red-400 outline-none"
          required>
        <button type="submit"
          class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-full transition">
          Subscribe
        </button>
      </form>

    </div>
  </section>


  <!-- About Us Section (Matches your website design) -->
  <section class="bg-gray-100 py-20">
    <div class="max-w-7xl mx-auto px-6">

      <!-- Section Title -->
      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-red-600 mb-4">About Us</h2>
        <p class="text-gray-600 max-w-2xl mx-auto text-lg">
          We make gifting simple, joyful, and secure for everyone.
        </p>
      </div>

      <!-- Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        <!-- Card 1 -->
        <div
          class="group bg-white rounded-2xl shadow-md p-8 flex flex-col items-center text-center h-72 transition-transform duration-300 hover:scale-105 hover:shadow-lg">

          <img src="https://img.icons8.com/ios-filled/64/26c6da/gift--v1.png" alt="Curated Gifts"
            class="w-16 h-16 mb-6">
          <h3 class="text-xl font-bold text-gray-900 mb-2">Curated Gifts</h3>
          <p class="text-gray-600 text-sm">
            Thoughtfully selected gifts to create unforgettable memories.
          </p>

        </div>

        <!-- Card 2 -->
        <div
          class="group bg-white rounded-2xl shadow-md p-8 flex flex-col items-center text-center h-72 transition-transform duration-300 hover:scale-105 hover:shadow-lg">

          <img src="https://img.icons8.com/ios-filled/64/26c6da/delivery.png" alt="Fast Delivery"
            class="w-16 h-16 mb-6">
          <h3 class="text-xl font-bold text-gray-900 mb-2">Fast Delivery</h3>
          <p class="text-gray-600 text-sm">
            Speedy and secure delivery with real-time tracking updates.
          </p>

        </div>

        <!-- Card 3 -->
        <div
          class="group bg-white rounded-2xl shadow-md p-8 flex flex-col items-center text-center h-72 transition-transform duration-300 hover:scale-105 hover:shadow-lg">

          <img src="https://img.icons8.com/ios-filled/64/26c6da/lock-2.png" alt="Secure Payment" class="w-16 h-16 mb-6">
          <h3 class="text-xl font-bold text-gray-900 mb-2">Secure Payment</h3>
          <p class="text-gray-600 text-sm">
            Encrypted transactions for peace of mind every time you shop.
          </p>

        </div>

      </div>
    </div>
  </section>




  <!-- Page Footer -->
  <?php include('footer.php'); ?>
  <!-- Preloader -->
  <div id="loader-wrapper">
    <div id="loader"></div>

    <div class="loader-section section-left"></div>
    <div class="loader-section section-right"></div>

  </div>
  <!-- Go To Top -->
  <div id="go-top" style="display: none;">
    <a title="Back to Top" href="#"><i class="fa fa-long-arrow-up" aria-hidden="true"></i></a>
  </div>

  <script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.3/js/materialize.min.js"></script>
  <script src="js/jquery.validate.min.js"></script>
  <script src="js/additional-methods.min.js"></script>
  <script src="js/jquery.scrollme.js"></script><!--scroll me jquery plugin-->
  <script src="js/animate-scroll.js"></script><!--animate on scroll me jquery plugin for animation-->
  <script src="js/init.js"></script>
  <script>
    //Slider
    $(document).ready(function () {
      $('.slider').slider({
        indicators: true,
        height: 600,
        interval: 4000
      });
    });
    //Dropdown Trigger
    $(document).ready(function () {
      $('.dropdown-trigger').dropdown({
        belowOrigin: true
      });
    });
    $(document).ready(function () {
      $('#go-top').click(function () {
        $("html, body").animate({ scrollTop: 0 }, 600);
        return false;
      });
    });
  
    //parallax
    $(document).ready(function () {
      $('.parallax').parallax()
    })
    // Preloader
    $(document).ready(function () {
      $(window).on('load', function () {
        setTimeout(function () {
          $('body').addClass('loaded');
        }, 1);
      });
    });
  
    //Go Top 
    var pxShow = 100; // height on which the button will show
    var fadeInTime = 400; // how slow/fast you want the button to show
    var fadeOutTime = 400; // how slow/fast you want the button to hide
  
    // Show or hide the sticky footer button
    jQuery(window).scroll(function () {
  
      if (jQuery(window).scrollTop() >= pxShow) {
        jQuery("#go-top").fadeIn(fadeInTime);
      } else {
        jQuery("#go-top").fadeOut(fadeOutTime);
      }
  
    });
    $(document).ready(function () {
      $('textarea#textarea2').characterCounter();
    });
  </script>

<style>
  nav .dropdown-trigger, 
  nav .dropdown-trigger:hover, 
  nav .nav-item:hover, 
  nav .nav-wrapper ul li a:hover {
    background-color: transparent !important;
  }
</style>
</body>

</html>