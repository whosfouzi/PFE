<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['id'])) {
    // Redirect to login page, preserving the current URL for redirection after login
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? 'my_account.php#likes';
    header("Location: login.php");
    exit();
}

// Database connection
$db = new mysqli("localhost", "root", "", "giftstore");

if ($db->connect_error) {
    // Log the error and redirect with a generic error message
    error_log("Database connection failed: " . $db->connect_error);
    header("Location: my_account.php?error=db_connect_failed#likes"); // Ensure error is before hash
    exit();
}

$user_id = $_SESSION['id'];
$product_id = $_GET['id'] ?? null; // Get product ID from GET request
$return_url = $_GET['return_url'] ?? 'my_account.php#likes'; // Get return URL

// Function to safely add query parameters to a URL that might contain a hash
function addUrlParam($url, $paramName, $paramValue) {
    $parts = parse_url($url);
    $query = isset($parts['query']) ? $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    parse_str($query, $queryParams); // Parse existing query string into an array
    $queryParams[$paramName] = $paramValue; // Add new parameter
    $newQuery = http_build_query($queryParams); // Build new query string

    $newUrl = '';
    if (isset($parts['scheme'])) $newUrl .= $parts['scheme'] . '://';
    if (isset($parts['host'])) $newUrl .= $parts['host'];
    if (isset($parts['port'])) $newUrl .= ':' . $parts['port'];
    if (isset($parts['path'])) $newUrl .= $parts['path'];

    if (!empty($newQuery)) {
        $newUrl .= '?' . $newQuery;
    }
    $newUrl .= $fragment; // Always append fragment last

    return $newUrl;
}


// Validate product ID
if (!filter_var($product_id, FILTER_VALIDATE_INT)) {
    $redirect_to = addUrlParam($return_url, 'error', 'invalid_product_id');
    header("Location: " . $redirect_to);
    exit();
}

// Prepare and execute the DELETE statement
$stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");

if (!$stmt) {
    // Log the error and redirect
    error_log("Prepare statement failed: " . $db->error);
    $redirect_to = addUrlParam($return_url, 'error', 'prepare_failed');
    header("Location: " . $redirect_to);
    exit();
}

$stmt->bind_param("ii", $user_id, $product_id);

if ($stmt->execute()) {
    // Successfully removed
    $redirect_to = addUrlParam($return_url, 'message', 'removed');
    header("Location: " . $redirect_to);
} else {
    // Handle deletion error
    error_log("Error removing from wishlist: " . $stmt->error);
    $redirect_to = addUrlParam($return_url, 'error', 'delete_failed');
    header("Location: " . $redirect_to);
}

$stmt->close();
$db->close();
exit();
?>