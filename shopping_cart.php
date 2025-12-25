<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Fetch cart items
$sql = "SELECT sc.cart_id, b.title, b.isbn, b.selling_price, sc.quantity, (b.selling_price * sc.quantity) as subtotal 
        FROM shopping_cart sc 
        JOIN books b ON sc.isbn = b.isbn 
        WHERE sc.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_price = 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Your Shopping Cart</h1>
            <nav>
                <a href="customer_dashboard.php">Home</a>
                <a href="search_books.php">Continue Shopping</a>
            </nav>
        </div>

        <div class="table-container">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()):
                            $total_price += $row['subtotal'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td>$<?php echo $row['selling_price']; ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>$<?php echo $row['subtotal']; ?></td>
                                <td>
                                    <a href="remove_from_cart.php?id=<?php echo $row['cart_id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Remove</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <tr style="background-color: #f8f9fa; font-weight: bold;">
                            <td colspan="3" style="text-align: right;">Total:</td>
                            <td colspan="2" style="color: #667eea; font-size: 18px;">$<?php echo number_format($total_price, 2); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div style="text-align: right; margin-top: 20px;">
                    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                </div>

            <?php else: ?>
                <p style="text-align: center; padding: 20px;">Your cart is empty.</p>
                <div style="text-align: center;">
                    <a href="search_books.php" class="btn btn-secondary">Browse Books</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>