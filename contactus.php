<?php
session_start();

if(isset($_POST['submit']))
{
  //for contact form validation
$conn=mysqli_connect('127.0.0.1','root','','giftstore');
  $fname=$_POST['fname'];
  $lname=$_POST['lname'];
  $email=$_POST['email'];
  $phone=$_POST['phone'];
  $message=$_POST['message'];

  $sql="INSERT INTO contactus(fname,lname,email,phone,message) VALUES('$fname','$lname','$email','$phone','$message')";
  mysqli_query($conn,$sql);
}
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
      <link rel="stylesheet" href="navbar.css">
      <link rel="stylesheet" href="css/style1.css">

      <style>
        body{
          background-image: url("images/space1.jpg");
          background-size:cover;
          background-repeat:no-repeat;
        }
        .container{
          opacity:0.6;
        }
         /* Class for placement of error messages for jQuery Validate */
  .errorMessage {
       font-size: 18px;
  }
  .errorMessage i,
  .errorMessage [class^="mdi-"], .breadcrumb [class*="mdi-"],
  .errorMessage i.material-icons {
      display: inline-block;
      float: left;
      font-size: 24px;
  }
  .errorMessage:before {
      color: rgba(255, 255, 255, 0.7);
      vertical-align: top;
      display: inline-block;
      font-family: 'Material Icons';
      font-weight: normal;
      font-style: normal;
      font-size: 25px;
      margin: 0 10px 0 8px;
      -webkit-font-smoothing: antialiased;
  }
  .errorMessage:first-child:before {
      display: none;
  }


  .invalid {
    color: red;
    font-size: 1em;
  }

      </style>
   </head>
   
   <body>
      <!--navigation bar-->
      <?php include('navbar.php'); ?>
      <!-- Form Starts -->
      <!-- MODERN CONTACT SECTION -->
<div class="container" style="margin-top: 40px;">
  <div class="row">
    <div class="col s12 m10 offset-m1 l8 offset-l2">
      <div class="card white z-depth-3" style="border-radius: 16px;">
        <div class="card-content">
          <div class="center-align">
            <h4 class="pink-text text-darken-2" style="font-weight: bold;">
              <i class="material-icons" style="vertical-align: middle;">mail_outline</i>
              Contact Us
            </h4>
            <p class="grey-text text-darken-1">We’d love to hear from you! Send us your thoughts, feedback, or gift ideas 💝</p>
          </div>
          <div class="row" style="margin-top: 30px;">
            <form class="col s12" method="POST" action="contactus.php" id="contactForm">

              <div class="row">
                <div class="input-field col s12 m6">
                  <i class="material-icons prefix pink-text">account_circle</i>
                  <input name="fname" type="text" class="validate" required>
                  <label for="fname">First Name</label>
                </div>
                <div class="input-field col s12 m6">
                  <i class="material-icons prefix pink-text">account_circle</i>
                  <input name="lname" type="text" class="validate" required>
                  <label for="lname">Last Name</label>
                </div>
              </div>

              <div class="row">
                <div class="input-field col s12 m6">
                  <i class="material-icons prefix pink-text">email</i>
                  <input name="email" type="email" class="validate" required>
                  <label for="email">Email</label>
                </div>
                <div class="input-field col s12 m6">
                  <i class="material-icons prefix pink-text">phone</i>
                  <input name="phone" type="tel" class="validate" required>
                  <label for="phone">Phone</label>
                </div>
              </div>

              <div class="row">
                <div class="input-field col s12">
                  <i class="material-icons prefix pink-text">message</i>
                  <textarea name="message" class="materialize-textarea" data-length="500" required></textarea>
                  <label for="message">Your Message</label>
                </div>
              </div>

              <div class="row center-align">
                <button type="submit" class="btn pink darken-1 waves-effect waves-light" style="border-radius: 12px; font-weight: 500;">
                  <i class="material-icons left">send</i> Send Message
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

      <!-- Page Footer -->
      <?php include('footer.php'); ?>

  </body>
  <script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.3/js/materialize.min.js"></script>
  <script src="js/jquery.validate.min.js"></script>
  <script src="js/additional-methods.min.js"></script>
  <script src="js/init.js"></script>
  <script>
    //Dropdown Trigger
         $(document).ready(function(){
            $('.dropdown-trigger').dropdown({
               belowOrigin:true
            });
         });
         $(document).ready(function(){
            $('#go-top').click(function(){
            $("html, body").animate({ scrollTop: 0 }, 600);
            return false;
          });
        });
         //Preloader 
         $(document).ready(function() {
            $(window).load(function(){
                setTimeout(function(){
                    $('body').addClass('loaded');
                }, 1000);
            });
         }); 
         //Go Top 
            var pxShow = 100; // height on which the button will show
            var fadeInTime = 400; // how slow/fast you want the button to show
            var fadeOutTime = 400; // how slow/fast you want the button to hide

            // Show or hide the sticky footer button
            jQuery(window).scroll(function() {

               if (jQuery(window).scrollTop() >= pxShow) {
                  jQuery("#go-top").fadeIn(fadeInTime);
               } else {
                  jQuery("#go-top").fadeOut(fadeOutTime);
               }

           });
            //jQuery Validate defaults
        jQuery.validator.setDefaults({
            errorClass: 'errorMessage invalid',
            validClass: "valid",
            errorElement : 'div',
            errorPlacement: function(error, element) {
                var placement = $(element).data('error');
                if (placement) {
                    $(placement).append(error)
                } else {
                    error.insertAfter(element);
                }
            }
        });
        

      $("#contactForm").validate({
              rules: {
                  fname:  {
                      required: true,
                      pattern: /^[a-zA-Z ]+$/,
                      maxlength: 10
                  },
                  lname:  {
                      required: true,
                      pattern: /^[a-zA-Z ]+$/,
                      maxlength: 16
                  },
                  phone: {
                      required: true,
                      digits: true,
                      minlength: 10,
                      maxlength: 10
                  },
                  email: {
                      required: true,
                      email: true,
                      pattern: /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/,
                      maxlength: 64
                  }, 
                  message: {
                      required: true,
                      minlength: 6,
                      maxlength: 400
                  }
              }
          });
          function countChar(val) {
            var len = val.value.length;
            if (len >= 500) {
              val.value = val.value.substring(0, 500);
            } else {
              $('#charNum').text(len+"/500");
            }
          };
  </script>
</html>
