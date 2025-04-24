<link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">

<style>
  .footer {
    background:rgb(255, 255, 255);
    padding: 40px 20px 20px;
    color: #444;
    font-family: 'Poppins', sans-serif;
  }

  .footer .footer-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    gap: 30px;
  }

  .footer h4 {
    color: #56c8d8;
    margin-bottom: 15px;
    font-size: 18px;
  }

  .footer p, .footer a {
    font-size: 14px;
    line-height: 1.6;
    color: #333;
    text-decoration: none;
  }

  .footer a:hover {
    color: #e91e63;
  }

  .footer .footer-col {
    flex: 1 1 250px;
  }

  .footer .social a {
    margin-right: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #c0392b;
    font-weight: 500;
  }

  .footer-bottom {
    border-top: 1px solid #f9cbd8;
    text-align: center;
    padding-top: 15px;
    margin-top: 30px;
    font-size: 13px;
    color: #777;
  }

  @media (max-width: 768px) {
    .footer .footer-container {
      flex-direction: column;
      text-align: center;
    }

    .footer .social {
      justify-content: center;
    }
  }
</style>

<footer class="footer">
  <div class="footer-container">
    
    <!-- Column 1: About -->
    <div class="footer-col">
    <h4 style="font-family: 'Dancing Script', cursive; font-size: 2rem; display: flex; align-items: center;">
  <span style="color: #56c8d8;">Gift</span><span style="color: #c0392b;">Me</span>
</h4>

      <p>
        A curated gift destination for every occasion.<br>
        Safe payment, fast delivery, and thoughtful packaging.<br>
        Your joy is our priority.
      </p>
    </div>

    <!-- Column 2: Links -->
    <div class="footer-col">
      <h4>Quick Links</h4>
      <p><a href="index.php">Home</a></p>
      <p><a href="about.php">About Us</a></p>
      <p><a href="contact.php">Contact Us</a></p>
      <p><a href="signup.php">Create Account</a></p>
    </div>

    <!-- Column 3: Connect -->
    <div class="footer-col">
      <h4>Connect</h4>
      <p>Developed by Slimani Faouzi</p>
      <div class="social">
        <a href="#"><img src="https://img.icons8.com/ios-filled/20/000000/facebook--v1.png"/> Facebook</a>
        <a href="mailto:you@example.com"><img src="https://img.icons8.com/ios-filled/20/000000/email-open.png"/> Email</a>
      </div>
    </div>
  </div>

  <!-- Bottom Line -->
  <div class="footer-bottom">
    &copy; <?= date('Y') ?> GiftStore. All rights reserved.
  </div>
</footer>
