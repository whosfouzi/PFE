<?php
session_start();
$unique_id = time() . mt_rand();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Thank You - GiftStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include("navbar.php"); ?>

<div class="container mx-auto px-4 py-20 text-center">
  <div class="bg-white p-10 rounded-lg shadow-md max-w-xl mx-auto">
    <h1 class="text-3xl font-bold text-pink-600 mb-4">🎉 Thank You for Your Order!</h1>
    <p class="text-gray-700 text-lg mb-4">
      Your order has been placed successfully.
    </p>
    <p class="text-sm text-gray-500 mb-6">
      Reference ID: <span class="font-mono bg-gray-200 px-2 py-1 rounded"><?= $unique_id ?></span>
    </p>
    <a href="index.php" class="inline-block bg-pink-600 text-white px-6 py-3 rounded hover:bg-pink-700 transition">
      Back to Home
    </a>
  </div>
</div>

<?php include("footer.php"); ?>
</body>
</html>
