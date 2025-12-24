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

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin';
}

// Check authentication
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Get statistics
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'Customer'")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM publisher_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
$low_stock = $conn->query("SELECT COUNT(*) as count FROM books WHERE quantity_in_stock < threshold_quantity")->fetch_assoc()['count'];

// Get recent books
$recent_books = $conn->query("SELECT b.*, p.name as publisher_name 
                               FROM books b 
                               LEFT JOIN publishers p ON b.publisher_id = p.publisher_id 
                               ORDER BY b.created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bookstore</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Admin Dashboard</h1>
            <nav>
                <a href="add_book.php">Add Book</a>
                <a href="manage_books.php">Manage Books</a>
                <a href="manage_orders.php">Orders</a>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Total Books</h3>
                <div class="value"><?php echo $total_books; ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Customers</h3>
                <div class="value"><?php echo $total_customers; ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Pending Orders</h3>
                <div class="value"><?php echo $pending_orders; ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Low Stock Items</h3>
                <div class="value" style="color: #dc3545;"><?php echo $low_stock; ?></div>
            </div>
        </div>
        
        <div class="table-container">
            <h2>Recently Added Books</h2>
            <table>
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Title</th>
                        <th>Publisher</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($book = $recent_books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['publisher_name']); ?></td>
                        <td><span class="badge badge-success"><?php echo $book['category']; ?></span></td>
                        <td>$<?php echo number_format($book['selling_price'], 2); ?></td>
                        <td>
                            <?php if ($book['quantity_in_stock'] < $book['threshold_quantity']): ?>
                                <span class="badge badge-danger"><?php echo $book['quantity_in_stock']; ?></span>
                            <?php else: ?>
                                <span class="badge badge-success"><?php echo $book['quantity_in_stock']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_book.php?isbn=<?php echo $book['isbn']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>