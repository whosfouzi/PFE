<footer class="bg-gradient-to-br from-gray-900 to-gray-700 text-white py-16 px-6 relative overflow-hidden">
  <div class="absolute inset-0 z-0 opacity-10">
    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice">
      <defs>
        <radialGradient id="gradient1" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#56c8d8" stop-opacity="0.1"></stop>
          <stop offset="100%" stop-color="transparent"></stop>
        </radialGradient>
        <radialGradient id="gradient2" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#ef4444" stop-opacity="0.1"></stop>
          <stop offset="100%" stop-color="transparent"></stop>
        </radialGradient>
      </defs>
      <circle cx="20" cy="20" r="15" fill="url(#gradient1)"></circle>
      <circle cx="80" cy="80" r="20" fill="url(#gradient2)"></circle>
      <circle cx="50" cy="0" r="10" fill="url(#gradient1)"></circle>
      <circle cx="0" cy="50" r="12" fill="url(#gradient2)"></circle>
      <circle cx="100" cy="50" r="18" fill="url(#gradient1)"></circle>
    </svg>
  </div>

  <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 relative z-10">

    <div class="col-span-1 md:col-span-2 lg:col-span-1">
      <h5 class="text-turquoise-primary dancing-script-font text-5xl font-bold mb-6 drop-shadow-lg">
        Sefar<span class="text-red-400">Gifts</span>
      </h5>
      <p class="text-gray-300 text-base leading-relaxed">
        Your premier destination for thoughtful and unique gifts. We are dedicated to creating memorable experiences through safe payments, fast delivery, and exquisite packaging.
      </p>
    </div>

    <div class="col-span-1">
      <h5 class="text-2xl font-semibold text-white mb-6 border-b-2 border-turquoise-primary pb-2 inline-block">Quick Links</h5>
      <ul class="space-y-3 mt-4">
        <li><a href="index.php" class="text-gray-300 hover:text-turquoise-primary transition-all duration-300 font-medium text-lg">Home</a></li>
        <li><a href="products.php" class="text-gray-300 hover:text-turquoise-primary transition-all duration-300 font-medium text-lg">Shop Gifts</a></li>
        <li><a href="aboutus.php" class="text-gray-300 hover:text-turquoise-primary transition-all duration-300 font-medium text-lg">About Us</a></li>
        <li><a href="contactus.php" class="text-gray-300 hover:text-turquoise-primary transition-all duration-300 font-medium text-lg">Contact Us</a></li>
        <?php if (!isset($_SESSION['id'])): ?>
            <li><a href="signup.php" class="text-gray-300 hover:text-turquoise-primary transition-all duration-300 font-medium text-lg">Create Account</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="col-span-1">
      <h5 class="text-2xl font-semibold text-white mb-6 border-b-2 border-red-400 pb-2 inline-block">Support</h5>
      <ul class="space-y-3 mt-4">
        <li><a href="my_account.php#orders" class="text-gray-300 hover:text-red-400 transition-all duration-300 font-medium text-lg">My Orders</a></li>
        <li><a href="my_account.php#security" class="text-gray-300 hover:text-red-400 transition-all duration-300 font-medium text-lg">Change Password</a></li>
        <li><a href="my_account.php#likes" class="text-gray-300 hover:text-red-400 transition-all duration-300 font-medium text-lg">My Likes</a></li>
        <li><a href="#" class="text-gray-300 hover:text-red-400 transition-all duration-300 font-medium text-lg">FAQ</a></li>
      </ul>
    </div>

    <div class="col-span-1">
      <h5 class="text-2xl font-semibold text-white mb-6 border-b-2 border-turquoise-primary pb-2 inline-block">Connect With Us</h5>
      <p class="text-gray-300 text-base mb-6">Developed with passion by Slimani Faouzi</p>
      <div class="flex space-x-6">
        <a href="#" class="text-gray-300 hover:text-turquoise-primary transition-all duration-300 text-3xl transform hover:scale-110" aria-label="Facebook">
          <i class="fab fa-facebook-square"></i>
        </a>
        <a href="#" class="text-gray-300 hover:text-turquoise-primary transition-all duration-300 text-3xl transform hover:scale-110" aria-label="Instagram">
          <i class="fab fa-instagram"></i>
        </a>
        <a href="#" class="text-gray-300 hover:text-turquoise-primary transition-all duration-300 text-3xl transform hover:scale-110" aria-label="Twitter">
          <i class="fab fa-twitter-square"></i>
        </a>
        <a href="mailto:fouzi.slimani75@gmail.com" class="text-gray-300 hover:text-turquoise-primary transition-all duration-300 text-3xl transform hover:scale-110" aria-label="Email">
          <i class="fas fa-envelope-square"></i>
        </a>
      </div>
    </div>
  </div>

  <div class="border-t border-gray-700 mt-16 pt-8 text-center text-gray-400 text-sm relative z-10">
    © 2025 SefarGifts. All rights reserved.
  </div>
</footer>

<style>
  /* Custom styles for the footer */
  .shadow-inner-top {
    box-shadow: inset 0 8px 15px -5px rgba(0, 0, 0, 0.3);
  }
  /* Additional styles for the new footer design */
  footer {
    position: relative;
  }
</style>