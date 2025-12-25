<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// --- Report A: Total sales for previous month (Req 6a) ---
$sql_a = "SELECT SUM(total_price) as total FROM customer_orders 
          WHERE MONTH(order_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
          AND YEAR(order_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)";
$res_a = $conn->query($sql_a)->fetch_assoc();

// --- Report B: Sales for a certain day (Req 6b) ---
$target_date = $_POST['target_date'] ?? date('Y-m-d');
$stmt_b = $conn->prepare("SELECT SUM(total_price) as total FROM customer_orders WHERE DATE(order_date) = ?");
$stmt_b->bind_param("s", $target_date);
$stmt_b->execute();
$res_b = $stmt_b->get_result()->fetch_assoc();

// --- Report C: Top 5 Customers (Last 3 Months) (Req 6c) ---
$sql_c = "SELECT u.username, SUM(o.total_price) as total_spent 
          FROM users u JOIN customer_orders o ON u.user_id = o.user_id 
          WHERE o.order_date >= NOW() - INTERVAL 3 MONTH 
          GROUP BY u.user_id ORDER BY total_spent DESC LIMIT 5";
$res_c = $conn->query($sql_c);

// --- Report D: Top 10 Selling Books (Last 3 Months) (Req 6d) ---
$sql_d = "SELECT b.title, SUM(oi.quantity) as total_sold 
          FROM books b JOIN order_items oi ON b.isbn = oi.isbn 
          JOIN customer_orders o ON oi.order_id = o.order_id 
          WHERE o.order_date >= NOW() - INTERVAL 3 MONTH 
          GROUP BY b.isbn ORDER BY total_sold DESC LIMIT 10";
$res_d = $conn->query($sql_d);

// --- Report E: Replenishment Orders count per book (Req 6e) ---
$sql_e = "SELECT b.title, b.isbn, COUNT(po.order_id) as replenishment_count 
          FROM books b LEFT JOIN publisher_orders po ON b.isbn = po.isbn 
          GROUP BY b.isbn";
$res_e = $conn->query($sql_e);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reports - Admin Only</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .report-section { background: #fff; padding: 20px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .stat-box { font-size: 24px; font-weight: bold; color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin System Reports</h1>
        <nav><a href="admin_dashboard.php" class="btn">← Back to Dashboard</a></nav>

        <div class="report-section">
            <h3>a) Total Sales (Previous Month)</h3>
            <div class="stat-box">$<?php echo number_format($res_a['total'] ?? 0, 2); ?></div>
        </div>

        <div class="report-section">
            <h3>b) Total Sales for a Certain Day</h3>
            <form method="POST" style="margin-bottom: 15px;">
                <input type="date" name="target_date" value="<?php echo $target_date; ?>" required>
                <button type="submit" class="btn btn-primary">Calculate Sales</button>
            </form>
            <div class="stat-box">$<?php echo number_format($res_b['total'] ?? 0, 2); ?></div>
        </div>

        <div class="report-section">
            <h3>c) Top 5 Customers (Last 3 Months)</h3>
            <table>
                <tr><th>Customer Username</th><th>Total Amount Spent</th></tr>
                <?php while($row = $res_c->fetch_assoc()): ?>
                    <tr><td><?php echo $row['username']; ?></td><td>$<?php echo number_format($row['total_spent'], 2); ?></td></tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="report-section">
            <h3>d) Top 10 Selling Books (Last 3 Months)</h3>
            <table>
                <tr><th>Book Title</th><th>Total Copies Sold</th></tr>
                <?php while($row = $res_d->fetch_assoc()): ?>
                    <tr><td><?php echo $row['title']; ?></td><td><?php echo $row['total_sold']; ?></td></tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="report-section">
            <h3>e) Replenishment Orders per Book</h3>
            <p><small>(Number of times you ordered this book from publishers)</small></p>
            <table>
                <tr><th>ISBN</th><th>Book Title</th><th>Times Ordered</th></tr>
                <?php while($row = $res_e->fetch_assoc()): ?>
                    <tr><td><?php echo $row['isbn']; ?></td><td><?php echo $row['title']; ?></td><td><?php echo $row['replenishment_count']; ?></td></tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>