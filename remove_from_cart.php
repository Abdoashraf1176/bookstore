<?php
require_once 'config.php';
requireLogin();

if (isset($_GET['id'])) {
    $conn = getDBConnection();
    $cart_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Only allow user to delete their own items
    $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE cart_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $conn->close();
}

header("Location: shopping_cart.php");
exit();
