<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM customer_orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>
    <title>My Orders</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Order History</h1>
            <nav><a href="customer_dashboard.php">Home</a></nav>
        </div>

        <div class="table-container">
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div style="border: 1px solid #ddd; margin-bottom: 20px; border-radius: 5px; overflow: hidden;">
                        <div style="background: #f8f9fa; padding: 10px 20px; display: flex; justify-content: space-between;">
                            <strong>Order #<?php echo $order['order_id']; ?></strong>
                            <span>Date: <?php echo $order['order_date']; ?></span>
                            <span class="badge badge-success"><?php echo $order['status']; ?></span>
                        </div>
                        <div style="padding: 15px;">
                            <table style="margin-top: 0;">
                                <?php
                                $oid = $order['order_id'];
                                $items_sql = "SELECT oi.*, b.title FROM order_items oi JOIN books b ON oi.isbn = b.isbn WHERE order_id = $oid";
                                $items = $conn->query($items_sql);
                                while ($item = $items->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                        <td>Qty: <?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo $item['price_per_unit']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </table>
                            <div style="text-align: right; margin-top: 10px; font-weight: bold;">
                                Total Paid: $<?php echo $order['total_price']; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="padding: 20px; text-align: center;">No past orders found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>