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
  <!-- Font Awesome Bootstrap -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <!--Materialized CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.3/css/materialize.min.css">
  <!-- CSS -->
  <link rel="stylesheet" href="css/go_to.css">

  <link rel="stylesheet" href="css/style1.css">
  <link rel="stylesheet" href="navbar.css">


</head>

<body>
  <!--navigation bar-->
  <?php include('navbar.php'); ?>
  <!--slider-->


  <section class="info-section">
  <div class="container">
    <div class="row">

      <!-- ABOUT US -->
      <div class="col s12 m6 l4">
        <div class="info-box pink lighten-5">
          <h4 class="pink-text text-darken-2">About Us</h4>
          <p>
            GiftStore is your one-stop shop for thoughtful presents. We offer a wide variety of curated gift collections, safe online shopping, and outstanding customer support to make gifting joyful and stress-free.
          </p>
        </div>
      </div>

      <!-- QUICK DELIVERY -->
      <div class="col s12 m6 l4">
        <div class="info-box pink lighten-4">
          <h4 class="pink-text text-darken-2">Quick Delivery</h4>
          <p>
            Enjoy fast, secure delivery with real-time tracking and safe banking. We make sure your gift reaches the right place at the right time — safely and beautifully.
          </p>
        </div>
      </div>

      <!-- SECURE PAYMENT -->
      <div class="col s12 m6 l4">
        <div class="info-box pink lighten-5">
          <h4 class="pink-text text-darken-2">Secure Payment</h4>
          <p>
            Your privacy and security are our priority. GiftStore uses encrypted payment gateways and trusted banking solutions to ensure safe, worry-free transactions.
          </p>
        </div>
      </div>

    </div>
  </div>
</section>



  <!--parallax-->

  <!--section-->
  <!--   <section class="container section" id="photo's">
      <div class="row">
        <div class="col s12 l4">
          <img src="images/space.jpg" class="responsive-img">
        </div>
        <div class="col s12 l6">
          <h3 class="pink-text text-accent-3">Amazing Gifts</h3>
          <p> escription goes here!!</p>
        </div>
      </div>
      <div class="row">
        <div class="col s12 l4">
          <img src="images/space.jpg" class="responsive-img">
        </div>
        <div class="col s12 l6">
          <h3 class="pink-text text-accent-3">Amazing Gifts</h3>
          <p>Description goes here!!</p>
        </div>
      </div>
      <div class="row">
        <div class="col s12 l4">
          <img src="images/space.jpg" class="responsive-img">
        </div>
        <div class="col s12 l6">
          <h3 class="pink-text text-accent-3">Amazing Gifts</h3>
          <p>Description goes here!!</p>
        </div>
      </div>
    </section> -->

  <!-- EMOTIONALLY THEMED GIFT CATEGORIES -->
<!-- THEMED CATEGORY SECTION FOR GIFTSTORE -->
<section class="category-section categories-background">
  <div class="container">
    <h3 class="center-align white-text section-title">Explore Gift Categories</h3>
    <div class="row">

      <!-- Each category -->
      <div class="col s12 m6 l3">
        <div class="category-card z-depth-2">
          <div class="card-image">
            <img src="images/categories/couples.jpg" alt="For Couples">
          </div>
          <a href="#">
            <div class="card-label">For Couples</div>
          </a>
        </div>
      </div>

      <div class="col s12 m6 l3">
        <div class="category-card z-depth-2">
          <div class="card-image">
            <img src="images/categories/love.jpg" alt="Love & Romance">
          </div>
          <a href="#">
            <div class="card-label">Love & Romance</div>
          </a>
        </div>
      </div>

      <div class="col s12 m6 l3">
        <div class="category-card z-depth-2">
          <div class="card-image">
            <img src="images/categories/giftideas.jpg" alt="Gift Ideas">
          </div>
          <a href="#">
            <div class="card-label">Gift Ideas</div>
          </a>
        </div>
      </div>

      <div class="col s12 m6 l3">
        <div class="category-card z-depth-2">
          <div class="card-image">
            <img src="images/categories/occasionpacks.jpg" alt="Occasion Packs">
          </div>
          <a href="#">
            <div class="card-label">Occasion Packs</div>
          </a>
        </div>
      </div>

      <div class="col s12 m6 l3">
        <div class="category-card z-depth-2">
          <div class="card-image">
            <img src="images/categories/surprise.jpg" alt="Surprise Boxes">
          </div>
          <a href="#">
            <div class="card-label">Surprise Boxes</div>
          </a>
        </div>
      </div>

      <div class="col s12 m6 l3">
        <div class="category-card z-depth-2">
          <div class="card-image">
            <img src="images/categories/custom.jpg" alt="Personalized Gifts">
          </div>
          <a href="#">
            <div class="card-label">Personalized Gifts</div>
          </a>
        </div>
      </div>

      <div class="col s12 m6 l3">
        <div class="category-card z-depth-2">
          <div class="card-image">
            <img src="images/categories/friends.jpg" alt="For Friends">
          </div>
          <a href="#">
            <div class="card-label">For Friends</div>
          </a>
        </div>
      </div>

      <div class="col s12 m6 l3">
        <div class="category-card z-depth-2">
          <div class="card-image">
            <img src="images/categories/selfcare.jpg" alt="Self-Care">
          </div>
          <a href="#">
            <div class="card-label">Self-Care</div>
          </a>
        </div>
      </div>

    </div>
  </div>
  <div class="parallax">
    <img src="images/space.jpg" alt="Space background">
  </div>
</section>





  <!-- 
<section class="quick-delivery">
  <div class="container center-align">
    <i class="material-icons large pink-text text-darken-2">local_shipping</i>
    <h3 class="pink-text text-darken-2">Quick & Secure Delivery</h3>
    <p class="flow-text">
      At GiftStore, we prioritize your time. Enjoy fast delivery, secure payments, and real-time order tracking — all while browsing a unique selection of gifts made with love.
    </p>
  </div>
</section>
-->




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
</body>
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
      }, 1000);
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

</html>