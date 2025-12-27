<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();

    $user_id = $_SESSION['user_id'];
    $isbn = $_POST['isbn'];
    $qty = max(1, intval($_POST['quantity'])); // always >= 1

    // Check if book already exists in cart
    $check = $conn->prepare(
        "SELECT quantity FROM shopping_cart WHERE user_id = ? AND isbn = ?"
    );
    $check->bind_param("is", $user_id, $isbn);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // SET quantity (not add)
        $stmt = $conn->prepare(
            "UPDATE shopping_cart 
             SET quantity = ? 
             WHERE user_id = ? AND isbn = ?"
        );
        $stmt->bind_param("iis", $qty, $user_id, $isbn);
    } else {
        // Insert new row
        $stmt = $conn->prepare(
            "INSERT INTO shopping_cart (user_id, isbn, quantity)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("isi", $user_id, $isbn, $qty);
    }

    $stmt->execute();
    $conn->close();
}

// Redirect back
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
