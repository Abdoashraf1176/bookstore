<?php
require_once 'config.php';

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