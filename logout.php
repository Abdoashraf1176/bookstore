<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306'); // MySQL default port
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookstore_system');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

$conn = getDBConnection();

// Clear shopping cart if customer is logging out
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Customer') {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

$conn->close();

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>