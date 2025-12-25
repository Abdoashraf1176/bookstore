<?php
require_once 'config.php';
requireAdmin();
$conn = getDBConnection();

if (isset($_GET['confirm_id'])) {
    $order_id = intval($_GET['confirm_id']);
    // Update stock and status (Requirement 4b)
    $sql = "UPDATE books b 
            JOIN publisher_orders o ON b.isbn = o.isbn 
            SET b.quantity_in_stock = b.quantity_in_stock + o.quantity, o.status = 'Confirmed' 
            WHERE o.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    header("Location: admin_dashboard.php?msg=Order Confirmed");
    exit();
}

$pending_orders = $conn->query("SELECT o.*, b.title FROM publisher_orders o JOIN books b ON o.isbn = b.isbn WHERE o.status = 'Pending'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Publisher Orders</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header"><h1>Pending Orders</h1><nav><a href="admin_dashboard.php">Back</a></nav></div>
        <div style="padding: 30px;">
            <table>
                <thead><tr><th>Book</th><th>Qty</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($row = $pending_orders->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['title']; ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td><?php echo $row['order_date']; ?></td>
                        <td><a href="?confirm_id=<?php echo $row['order_id']; ?>" class="btn btn-primary">Confirm Receipt</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>