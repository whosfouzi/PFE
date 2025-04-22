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

  <style>

  </style>
</head>

<body>
  <!--navigation bar-->
  <?php include('navbar.php'); ?>
  <!--navigation ends-->
  <div class="row">
    <div class="col s12 m6 l3">
      <div class="card medium">
        <div class="grad1">
          <div class="card-image waves-effect waves-block waves-light center-align">
            <i class="material-icons md-166 white-text activator">add_box</i>
          </div>
        </div>
        <div class="card-content">
          <span class="card-title activator grey-text text-darken-4">Maximum Opportunities<br><i
              class="material-icons right" style="margin-top:5px;">more_vert</i>
          </span>
        </div>
        <div class="card-reveal">
          <span class="card-title grey-text text-darken-4">Maximum Opportunities<i
              class="material-icons right">close</i></span>
          <p> Information to be added</p>
        </div>
      </div>
    </div>
    <div class="col s12 m6 l3">
      <div class="card medium">
        <div class="grad1">
          <div class="card-image waves-effect waves-block waves-light center-align">
            <i class="material-icons md-166 white-text activator">collections</i>
          </div>
        </div>
        <div class="card-content">
          <span class="card-title activator grey-text text-darken-4">Great Stock<br><i
              class="material-icons right">more_vert</i>
          </span>
        </div>
        <div class="card-reveal">
          <span class="card-title grey-text text-darken-4">Great Stock<i class="material-icons right">close</i></span>
          <p> Information to be added</p>
        </div>
      </div>
    </div>
    <div class="col s12 m6 l3">
      <div class="card medium">
        <div class="grad1">
          <div class="card-image waves-effect waves-block waves-light center-align">
            <i class="material-icons md-166 white-text activator">flash_on</i>
          </div>
        </div>
        <div class="card-content">
          <span class="card-title activator grey-text text-darken-4">Faster Service<br><i
              class="material-icons right">more_vert</i>
          </span>
        </div>
        <div class="card-reveal">
          <span class="card-title grey-text text-darken-4">Faster Service<i
              class="material-icons right">close</i></span>
          <p> Information to be added</p>
        </div>
      </div>
    </div>
    <div class="col s12 m6 l3">
      <div class="card medium">
        <div class="grad1">
          <div class="card-image waves-effect waves-block waves-light center-align">
            <i class="material-icons md-166 white-text activator">perm_identity</i>
          </div>
        </div>
        <div class="card-content">
          <span class="card-title activator grey-text text-darken-4">User Support<br><i
              class="material-icons right">more_vert</i>
          </span>
        </div>
        <div class="card-reveal">
          <span class="card-title grey-text text-darken-4">User Support<i class="material-icons right">close</i></span>
          <p> Information to be added</p>
        </div>
      </div>
    </div>
  </div>
  <div id="go-top" style="display: none;">
    <a title="Back to Top" href="#"><i class="fa fa-long-arrow-up" aria-hidden="true"></i></a>
  </div>
  <!-- Page Footer -->
  <?php include('footer.php'); ?>


</body>
<script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.3/js/materialize.min.js"></script>
<script src="js/jquery.validate.min.js"></script>
<script src="js/additional-methods.min.js"></script>
<script src="js/init.js"></script>
<script type="text/javascript">
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
  //Preloader 
  $(document).ready(function () {
    $(window).load(function () {
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
</script>

</html>