<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get Cart Total
$stmt = $conn->prepare("SELECT SUM(b.selling_price * sc.quantity) as total FROM shopping_cart sc JOIN books b ON sc.isbn = b.isbn WHERE sc.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

if (!$total) {
    header("Location: shopping_cart.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cc_num = $_POST['card_number'];
    $expiry = $_POST['expiry'];

    // Simple validation (Requirement: "Completed if info is appropriate")
    if (strlen($cc_num) == 16 && !empty($expiry)) {

        $conn->begin_transaction();

        try {
            // 1. Create Order
            $order_sql = "INSERT INTO customer_orders (user_id, total_price, credit_card_number, card_expiry_date, status) VALUES (?, ?, ?, ?, 'Completed')";
            $stmt = $conn->prepare($order_sql);
            $stmt->bind_param("idss", $user_id, $total, $cc_num, $expiry);
            $stmt->execute();
            $order_id = $conn->insert_id;

            // 2. Move items from Cart to OrderItems and Update Stock
            $cart_sql = "SELECT sc.isbn, sc.quantity, b.selling_price FROM shopping_cart sc JOIN books b ON sc.isbn = b.isbn WHERE sc.user_id = ?";
            $cart_stmt = $conn->prepare($cart_sql);
            $cart_stmt->bind_param("i", $user_id);
            $cart_stmt->execute();
            $cart_items = $cart_stmt->get_result();

            while ($item = $cart_items->fetch_assoc()) {
                // Insert Order Item
                $item_sql = "INSERT INTO order_items (order_id, isbn, quantity, price_per_unit) VALUES (?, ?, ?, ?)";
                $i_stmt = $conn->prepare($item_sql);
                $i_stmt->bind_param("isid", $order_id, $item['isbn'], $item['quantity'], $item['selling_price']);
                $i_stmt->execute();

                // Deduct Stock
                $stock_sql = "UPDATE books SET quantity_in_stock = quantity_in_stock - ? WHERE isbn = ?";
                $s_stmt = $conn->prepare($stock_sql);
                $s_stmt->bind_param("is", $item['quantity'], $item['isbn']);
                $s_stmt->execute();
            }

            // 3. Clear Cart
            $clear_sql = "DELETE FROM shopping_cart WHERE user_id = ?";
            $c_stmt = $conn->prepare($clear_sql);
            $c_stmt->bind_param("i", $user_id);
            $c_stmt->execute();

            $conn->commit();
            $success = "Order placed successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Transaction failed: " . $e->getMessage();
        }
    } else {
        $error = "Invalid Credit Card details (Must be 16 digits).";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Checkout</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container" style="max-width: 600px; margin-top: 50px;">
        <div class="header">
            <h1>Checkout</h1>
            <nav><a href="shopping_cart.php">Back</a></nav>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <br><br><a href="customer_dashboard.php">Return to Dashboard</a>
            </div>
        <?php else: ?>

            <div class="form-container">
                <h2 style="text-align: center; margin-bottom: 20px;">Total to Pay: $<?php echo number_format($total, 2); ?></h2>

                <?php if ($error) echo "<div class='alert alert-error'>$error</div>"; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Credit Card Number (16 digits)</label>
                        <input type="text" name="card_number" maxlength="16" placeholder="1234567890123456" required>
                    </div>

                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="month" name="expiry" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Pay Now</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>