<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    $isbn = $_POST['isbn'];
    $qty = intval($_POST['quantity']);

    // Check if already in cart
    $check = $conn->prepare("SELECT * FROM shopping_cart WHERE user_id = ? AND isbn = ?");
    $check->bind_param("is", $user_id, $isbn);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Update quantity
        $stmt = $conn->prepare("UPDATE shopping_cart SET quantity = quantity + ? WHERE user_id = ? AND isbn = ?");
        $stmt->bind_param("iis", $qty, $user_id, $isbn);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO shopping_cart (user_id, isbn, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $user_id, $isbn, $qty);
    }

    $stmt->execute();
    $conn->close();
}

// Go back to previous page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
