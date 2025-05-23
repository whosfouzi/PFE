<?php
session_start();

// Database connection for fetching reviews
$db = new mysqli("localhost", "root", "", "giftstore");
if ($db->connect_error) {
    error_log("Database Connection failed in index.php: " . $db->connect_error);
    // In a production environment, you might want a more robust error page or message
    // For now, we'll just ensure $featured_reviews is empty if connection fails
    $featured_reviews = [];
} else {
    $db->set_charset("utf8mb4");

    // Fetch featured reviews (assuming 'is_featured' column exists in 'reviews' table)
    $stmt_featured_reviews = $db->prepare("
        SELECT r.rating, r.comment, u.username
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.is_featured = 1
        ORDER BY r.created_at DESC
        LIMIT 3
    "); // Limit to 3 for a clean display

    if ($stmt_featured_reviews) {
        $stmt_featured_reviews->execute();
        $featured_reviews = $stmt_featured_reviews->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_featured_reviews->close();
    } else {
        error_log("Featured Reviews Prepare failed in index.php: " . $db->error);
        $featured_reviews = [];
    }
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>GiftStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Pacifico&family=Dancing+Script:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Global styles for the magical theme */
    body {
      font-family: 'Inter', sans-serif;
      background: #f8fafc; /* Light background */
      color: #1f2937; /* Darker text for readability */
      overflow-x: hidden; /* Prevent horizontal scroll on some animations */
    }

    .pacifico-font {
      font-family: 'Pacifico', cursive;
    }

    .dancing-script-font {
      font-family: 'Dancing Script', cursive;
    }

    /* Custom colors for consistency with other pages */
    :root {
        --primary-color: #56c8d8; /* Turquoise */
        --secondary-color: #ef4444; /* Red */
        --accent-color: #facc15; /* Yellow */
        --text-dark: #1f2937;
        --text-light: #f9fafb;
        --card-bg: #ffffff;
        --shadow-light: rgba(0, 0, 0, 0.05);
        --shadow-medium: rgba(0, 0, 0, 0.1);
        --shadow-strong: rgba(0, 0, 0, 0.2);
    }

    .text-primary { color: var(--primary-color); }
    .bg-primary { background-color: var(--primary-color); }
    .border-primary { border-color: var(--primary-color); }
    .text-secondary { color: var(--secondary-color); }
    .bg-secondary { background-color: var(--secondary-color); }
    .border-secondary { border-color: var(--secondary-color); }
    .hover\:bg-primary-dark:hover { background-color: #45b1c0; } /* A darker turquoise */
    .hover\:bg-secondary-dark:hover { background-color: #dc2626; } /* A darker red */

    /* Hero Section Specific Styles */
    .hero-section {
        background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
        padding: 6rem 0;
        position: relative;
        overflow: hidden;
    }
    .hero-content {
        background: var(--card-bg);
        border-radius: 2rem;
        box-shadow: 0 25px 50px var(--shadow-medium);
        padding: 3rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .hero-text {
        flex: 1;
        min-width: 300px;
        text-align: center;
        padding-right: 2rem;
    }
    .hero-image-container {
        flex: 1;
        min-width: 300px;
        display: flex;
        justify-content: center;
        align-items: center;
        padding-left: 2rem;
    }
    .hero-image {
        border-radius: 1.5rem;
        box-shadow: 0 15px 30px var(--shadow-light);
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }

    /* Card Styles */
    .feature-card, .testimonial-card {
      background: var(--card-bg);
      border-radius: 1.5rem;
      box-shadow: 0 10px 30px var(--shadow-light);
      transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
      padding: 2.5rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    .feature-card:hover, .testimonial-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 15px 40px var(--shadow-medium);
    }

    /* Button styles */
    .btn-main {
      background-color: var(--secondary-color);
      color: var(--text-light);
      padding: 1rem 2.5rem;
      border-radius: 0.75rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .btn-main:hover {
      background-color: var(--secondary-color-dark);
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(239, 68, 68, 0.4);
    }

    /* Preloader styles (Vanilla JS compatible) */
    #loader-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
        overflow: hidden;
        background: #e0f7fa; /* Light turquoise background */
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity .7s ease-out, visibility .7s ease-out;
    }
    #loader {
        display: block;
        position: relative;
        left: 50%;
        top: 50%;
        width: 100px;
        height: 100px;
        margin: -50px 0 0 -50px;
        border: 5px solid var(--primary-color); /* Turquoise border */
        border-top-color: var(--secondary-color); /* Red top */
        border-radius: 50%;
        animation: spin 1.2s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .loaded #loader-wrapper {
        opacity: 0;
        visibility: hidden;
    }

    /* Go To Top Button */
    #go-top {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 99;
      background-color: var(--primary-color);
      color: white;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      transition: opacity 0.3s ease, transform 0.3s ease;
      opacity: 0; /* Hidden by default */
      transform: translateY(20px);
    }
    #go-top.show {
      opacity: 1;
      transform: translateY(0);
    }
    #go-top:hover {
      background-color: #45b1c0;
      transform: translateY(-3px);
    }
    /* Star Rating Styles for display */
    .static-rating {
        display: inline-block;
        font-size: 1.5rem; /* Larger stars */
        color: var(--accent-color); /* Gold color */
        letter-spacing: 2px; /* More space out stars */
    }
    .static-rating .empty-star {
        color: #e5e7eb; /* Light grey for empty stars */
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .hero-content {
            flex-direction: column;
            padding: 2rem;
        }
        .hero-text {
            padding-right: 0;
            margin-bottom: 2rem;
        }
        .hero-image-container {
            padding-left: 0;
        }
    }
  </style>
</head>

<body>

  <?php include('navbar.php'); ?>

  <section class="hero-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="hero-content">
        <div class="hero-text">
          <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 mb-4 leading-tight">
            Give the Gift of <span class="text-secondary dancing-script-font">Choice</span>
          </h1>
          <p class="text-lg sm:text-xl text-gray-700 mb-8 max-w-lg mx-auto">
            Send a sweet gift to friends, colleagues, or loved ones through email or text, letting them pick their perfect present.
          </p>
          <div class="mt-8">
            <a href="products.php" class="btn-main">
              <i class="fas fa-gift mr-2"></i> Explore Gifts
            </a>
          </div>
        </div>

        <div class="hero-image-container">
          <img src="images/ChatGPT Image Apr 28, 2025, 09_03_17 PM.png" alt="Gift Bouquet"
            class="h-full w-full max-w-lg object-cover object-center hero-image" />
        </div>
      </div>
    </div>
  </section>

  <section class="bg-white py-20">
    <div class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8">

      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-secondary mb-4 pacifico-font">What Our Customers Say</h2>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Real stories from happy gifters and delighted recipients.
        </p>
      </div>

      <?php if (!empty($featured_reviews)): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
          <?php foreach ($featured_reviews as $review): ?>
            <div class="testimonial-card bg-gray-50 p-8">
              <div class="static-rating mb-4">
                  <?php
                  $filled_stars = $review['rating'];
                  $empty_stars = 5 - $filled_stars;
                  echo str_repeat('★', $filled_stars);
                  echo str_repeat('<span class="empty-star">★</span>', $empty_stars);
                  ?>
              </div>
              <p class="text-gray-700 mb-4 italic text-base flex-grow">"<?= nl2br(htmlspecialchars($review['comment'])) ?>"</p>
              <h4 class="text-secondary font-bold mt-auto text-lg"> - <?= htmlspecialchars($review['username']) ?></h4>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-10 text-gray-500">
          <p>No featured reviews to display yet.</p>
          <p class="text-sm mt-2">Check back soon for more customer testimonials!</p>
        </div>
      <?php endif; ?>

    </div>
  </section>

  <section class="py-16 bg-white text-center">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <p class="text-secondary font-semibold text-sm uppercase mb-2 tracking-wide">Simple Steps to Joy</p>
      <h2 class="text-4xl font-bold text-gray-900 mb-6 pacifico-font">How Our Gifting Works</h2>
      <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-12">
        We've streamlined the gifting process to be as magical as the gifts themselves.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
        <div class="feature-card">
          <i class="fas fa-shopping-cart text-primary text-6xl mb-6"></i>
          <h3 class="text-2xl font-semibold text-gray-900 mb-3">1. Choose & Order</h3>
          <p class="text-gray-600 text-base flex-grow">Browse our exquisite collection, select the perfect gift, and complete your order with ease.</p>
        </div>

        <div class="feature-card">
          <i class="fas fa-box-tissue text-primary text-6xl mb-6"></i>
          <h3 class="text-2xl font-semibold text-gray-900 mb-3">2. Expertly Prepared</h3>
          <p class="text-gray-600 text-base flex-grow">Our dedicated team meticulously prepares and packages your gift with utmost care and attention to detail.</p>
        </div>

        <div class="feature-card">
          <i class="fas fa-truck text-primary text-6xl mb-6"></i>
          <h3 class="text-2xl font-semibold text-gray-900 mb-3">3. Delivered with Love</h3>
          <p class="text-gray-600 text-base flex-grow">Our reliable delivery partners ensure your thoughtful present arrives safely and promptly at its destination.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-gray-100 py-20">
    <div class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8">

      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-secondary mb-4 pacifico-font">Why SefarGifts Stands Out</h2>
        <p class="text-gray-600 max-w-2xl mx-auto text-lg">
          Experience the difference with our commitment to quality, speed, and unparalleled service.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 text-center">

        <div class="feature-card">
          <i class="fas fa-rocket text-primary text-6xl mb-4"></i>
          <h4 class="text-xl font-semibold text-gray-900 mb-2">Blazing Fast Delivery</h4>
          <p class="text-gray-600 text-sm">Your gifts arrive swiftly, ensuring smiles without the wait.</p>
        </div>

        <div class="feature-card">
          <i class="fas fa-gem text-primary text-6xl mb-4"></i>
          <h4 class="text-xl font-semibold text-gray-900 mb-2">Premium Quality Gifts</h4>
          <p class="text-gray-600 text-sm">Only the finest, handpicked items make it into our curated selection.</p>
        </div>

        <div class="feature-card">
          <i class="fas fa-life-ring text-primary text-6xl mb-4"></i>
          <h4 class="text-xl font-semibold text-gray-900 mb-2">Dedicated Customer Support</h4>
          <p class="text-gray-600 text-sm">Our friendly team is always here to assist you, every step of the way.</p>
        </div>

        <div class="feature-card">
          <i class="fas fa-lock text-primary text-6xl mb-4"></i>
          <h4 class="text-xl font-semibold text-gray-900 mb-2">Ironclad Security</h4>
          <p class="text-gray-600 text-sm">Shop with confidence knowing your data are fully protected.</p>
        </div>

      </div>

    </div>
  </section>

  <section class="bg-gray-100 py-20">
    <div class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8">

      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-secondary mb-4 pacifico-font">Our Story & Mission</h2>
        <p class="text-gray-600 max-w-2xl mx-auto text-lg">
          Dedicated to spreading joy through thoughtful and effortless gifting experiences.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

        <div class="feature-card">
          <i class="fas fa-hand-holding-heart text-primary text-6xl mb-6"></i>
          <h3 class="text-2xl font-bold text-gray-900 mb-2">Passion for Gifting</h3>
          <p class="text-gray-600 text-base">
            We believe every gift tells a story. Our passion drives us to make each one special.
          </p>
        </div>

        <div class="feature-card">
          <i class="fas fa-lightbulb text-primary text-6xl mb-6"></i>
          <h3 class="text-2xl font-bold text-gray-900 mb-2">Innovation & Simplicity</h3>
          <p class="text-gray-600 text-base">
            Constantly evolving our platform to make gifting easier and more intuitive for you.
          </p>
        </div>

        <div class="feature-card">
          <i class="fas fa-users text-primary text-6xl mb-6"></i>
          <h3 class="text-2xl font-bold text-gray-900 mb-2">Community Focused</h3>
          <p class="text-gray-600 text-base">
            Building connections and fostering happiness within our growing community of gifters.
          </p>
        </div>

      </div>
    </div>
  </section>


  <?php include('footer.php'); ?>


  <button id="go-top" title="Back to Top">
    <i class="fa fa-long-arrow-up" aria-hidden="true"></i>
  </button>

  <script>
    // Vanilla JS for preloader
    window.addEventListener('load', () => {
      const loaderWrapper = document.getElementById('loader-wrapper');
      if (loaderWrapper) {
        // Use a small timeout to ensure the CSS transition applies
        setTimeout(() => {
          loaderWrapper.classList.add('loaded');
        }, 100); // A small delay to ensure CSS transition takes effect
      }
    });

    // Vanilla JS for Go To Top button
    const goTopButton = document.getElementById('go-top');
    const pxShow = 100; // height on which the button will show

    window.addEventListener('scroll', () => {
      if (window.scrollY >= pxShow) {
        goTopButton.classList.add('show');
      } else {
        goTopButton.classList.remove('show');
      }
    });

    goTopButton.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  </script>

</body>

</html>
