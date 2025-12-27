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

function isCustomer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Customer';
}

// Check authentication
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Redirect admins to admin dashboard
if (!isCustomer()) {
    header("Location: admin_dashboard.php");
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get customer statistics
$cart_items_res = $conn->query("SELECT COUNT(*) as count FROM shopping_cart WHERE user_id = $user_id");
$cart_items = $cart_items_res ? $cart_items_res->fetch_assoc()['count'] : 0;

$total_orders_res = $conn->query("SELECT COUNT(*) as count FROM customer_orders WHERE user_id = $user_id");
$total_orders = $total_orders_res ? $total_orders_res->fetch_assoc()['count'] : 0;

// Get total spent
$total_spent_result = $conn->query("SELECT SUM(total_price) as total FROM customer_orders WHERE user_id = $user_id");
$total_spent = $total_spent_result->fetch_assoc()['total'] ?? 0;

// Get cart total
$cart_total_result = $conn->query("SELECT SUM(b.selling_price * sc.quantity) as total 
                                    FROM shopping_cart sc 
                                    JOIN books b ON sc.isbn = b.isbn 
                                    WHERE sc.user_id = $user_id");
$cart_total = $cart_total_result->fetch_assoc()['total'] ?? 0;

// Get recent books (Requirement 5c: Details and availability)
$featured_books = $conn->query("SELECT b.*, p.name as publisher_name,
                                 GROUP_CONCAT(a.name SEPARATOR ', ') as authors
                                 FROM books b
                                 LEFT JOIN publishers p ON b.publisher_id = p.publisher_id
                                 LEFT JOIN book_authors ba ON b.isbn = ba.isbn
                                 LEFT JOIN authors a ON ba.author_id = a.author_id
                                 WHERE b.quantity_in_stock > 0
                                 GROUP BY b.isbn
                                 ORDER BY b.created_at DESC
                                 LIMIT 6");

// Get categories with book counts
$categories = $conn->query("SELECT category, COUNT(*) as count 
                            FROM books 
                            WHERE quantity_in_stock > 0
                            GROUP BY category 
                            ORDER BY category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Bookstore</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .welcome-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center; }
        .welcome-section h2 { font-size: 32px; margin-bottom: 10px; }
        .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; padding: 30px; }
        .book-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 2px solid transparent; transition: 0.3s; }
        .book-card:hover { transform: translateY(-5px); border-color: #667eea; }
        .category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .category-card { background: white; padding: 20px; border-radius: 8px; text-align: center; text-decoration: none; color: #333; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .category-card:hover { background: #667eea; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Bookstore</h1>
            <nav>
                <a href="customer_dashboard.php">Home</a>
                <a href="search.php">Search Books</a>
                <a href="shopping_cart.php">Cart (<?php echo $cart_items; ?>)</a>
                <a href="order_history.php">My Orders</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
        
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?>! 👋</h2>
            <p>Find your next great read today</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Cart Items</h3>
                <div class="value"><?php echo $cart_items; ?></div>
            </div>
            <div class="dashboard-card">
                <h3>My Orders</h3>
                <div class="value"><?php echo $total_orders; ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Cart Value</h3>
                <div class="value">$<?php echo number_format($cart_total, 2); ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Total Spent</h3>
                <div class="value">$<?php echo number_format($total_spent, 2); ?></div>
            </div>
        </div>
        
        <div class="category-section" style="padding: 30px;">
            <h2>Browse by Category</h2>
            <div class="category-grid">
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <a href="search.php?category=<?php echo urlencode($cat['category']); ?>" class="category-card">
                        <h3><?php echo htmlspecialchars($cat['category']); ?></h3>
                        <div class="count"><?php echo $cat['count']; ?> books available</div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
        
        <div style="padding: 30px 30px 10px;">
            <h2>Featured Books</h2>
        </div>
        
        <div class="book-grid">
            <?php while ($book = $featured_books->fetch_assoc()): ?>
                <div class="book-card">
                    <span class="badge badge-success"><?php echo htmlspecialchars($book['category']); ?></span>
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <div class="author">By: <?php echo htmlspecialchars($book['authors']); ?></div>
                    <div class="price">$<?php echo number_format($book['selling_price'], 2); ?></div>
                    <div class="stock" style="color: green; font-weight: bold;">✓ Available: <?php echo $book['quantity_in_stock']; ?></div>
                    
                    <form method="POST" action="add_to_cart.php" style="margin-top: 15px; display: flex; gap: 5px;">
                        <input type="hidden" name="isbn" value="<?php echo $book['isbn']; ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $book['quantity_in_stock']; ?>" style="width: 50px; padding: 5px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Add to Cart</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>