<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306'); 
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

// Check authentication - Security Gate
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// 1. Get statistics for the dashboard cards
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'Customer'")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM publisher_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
$low_stock = $conn->query("SELECT COUNT(*) as count FROM books WHERE quantity_in_stock < threshold_quantity")->fetch_assoc()['count'];

// 2. Get recent books (Requirement 5c: Details and Availability)
$recent_books = $conn->query("SELECT b.*, p.name as publisher_name 
                               FROM books b 
                               LEFT JOIN publishers p ON b.publisher_id = p.publisher_id 
                               ORDER BY b.isbn DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bookstore System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Simple search bar styling */
        .search-area { background: #f4f4f4; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ddd; }
        .search-area input { padding: 10px; width: 70%; border: 1px solid #ccc; border-radius: 4px; }
        .badge-warning { background: #ffc107; color: #000; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Admin Control Panel</h1>
            <nav>
                <a href="add_book.php">Add Book</a>
                <a href="edit_book.php">Edit/Update Books</a>
                <a href="confirm_orders.php">Publisher Orders (<?php echo $pending_orders; ?>)</a>
                <a href="admin_reports.php">System Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
        
        <div class="search-area">
            <form action="search.php" method="GET" style="display: flex; gap: 10px;">
                <input type="text" name="search_text" placeholder="Search by ISBN, Title, Author, or Category...">
                <button type="submit" class="btn btn-primary">Search System</button>
            </form>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Total Titles</h3>
                <div class="value"><?php echo $total_books; ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Customers</h3>
                <div class="value"><?php echo $total_customers; ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Pending Supply</h3>
                <div class="value"><?php echo $pending_orders; ?></div>
            </div>
            
            <div class="dashboard-card">
                <h3>Low Stock Alert</h3>
                <div class="value" style="color: #dc3545;"><?php echo $low_stock; ?></div>
            </div>
        </div>
        
        <div class="table-container">
            <h2>Inventory Status & Availability</h2>
            <table>
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Book Title</th>
                        <th>Publisher</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($book = $recent_books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($book['publisher_name']); ?></td>
                        <td><span class="badge badge-success"><?php echo $book['category']; ?></span></td>
                        <td>$<?php echo number_format($book['selling_price'], 2); ?></td>
                        <td>
                            <?php if ($book['quantity_in_stock'] <= 0): ?>
                                <span class="badge badge-danger">Out of Stock</span>
                            <?php elseif ($book['quantity_in_stock'] < $book['threshold_quantity']): ?>
                                <span class="badge badge-warning">Low: <?php echo $book['quantity_in_stock']; ?></span>
                            <?php else: ?>
                                <span class="badge badge-success">Available: <?php echo $book['quantity_in_stock']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_book.php?isbn=<?php echo $book['isbn']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 11px;">Update</a>
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