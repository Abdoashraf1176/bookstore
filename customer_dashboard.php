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
$cart_items = $conn->query("SELECT COUNT(*) as count FROM shopping_cart WHERE user_id = $user_id")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM customer_orders WHERE user_id = $user_id")->fetch_assoc()['count'];

// Get total spent
$total_spent_result = $conn->query("SELECT SUM(total_price) as total FROM customer_orders WHERE user_id = $user_id");
$total_spent = $total_spent_result->fetch_assoc()['total'] ?? 0;

// Get cart total
$cart_total_result = $conn->query("SELECT SUM(b.selling_price * sc.quantity) as total 
                                   FROM shopping_cart sc 
                                   JOIN books b ON sc.isbn = b.isbn 
                                   WHERE sc.user_id = $user_id");
$cart_total = $cart_total_result->fetch_assoc()['total'] ?? 0;

// Get recent books (featured books)
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
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .welcome-section h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            padding: 30px;
        }
        
        .book-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid transparent;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }
        
        .book-card h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
            min-height: 45px;
        }
        
        .book-card .author {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .book-card .price {
            color: #667eea;
            font-size: 24px;
            font-weight: 700;
            margin: 15px 0;
        }
        
        .book-card .stock {
            font-size: 12px;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .category-section {
            padding: 30px;
            background: #f8f9fa;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .category-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: #333;
        }
        
        .category-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .category-card h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .category-card .count {
            font-size: 14px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Bookstore</h1>
            <nav>
                <a href="customer_dashboard.php">Home</a>
                <a href="search_books.php">Search Books</a>
                <a href="shopping_cart.php">Cart (<?php echo $cart_items; ?>)</a>
                <a href="order_history.php">My Orders</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
        
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h2>
            <p>Discover your next favorite book</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Shopping Cart</h3>
                <div class="value"><?php echo $cart_items; ?></div>
                <small>Items in cart</small>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo $total_orders; ?></div>
                <small>All time</small>
            </div>
            
            <div class="dashboard-card">
                <h3>Cart Total</h3>
                <div class="value">$<?php echo number_format($cart_total, 2); ?></div>
                <small>Current cart value</small>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Spent</h3>
                <div class="value">$<?php echo number_format($total_spent, 2); ?></div>
                <small>All time purchases</small>
            </div>
        </div>
        
        <div class="category-section">
            <h2 style="margin-bottom: 10px;">Browse by Category</h2>
            <p style="color: #666; margin-bottom: 20px;">Explore our collection of books</p>
            
            <div class="category-grid">
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <a href="search_books.php?category=<?php echo urlencode($cat['category']); ?>" class="category-card">
                        <h3><?php echo htmlspecialchars($cat['category']); ?></h3>
                        <div class="count"><?php echo $cat['count']; ?> books</div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
        
        <div style="padding: 30px 30px 10px;">
            <h2>Featured Books</h2>
            <p style="color: #666; margin-top: 5px;">Check out our latest additions</p>
        </div>
        
        <div class="book-grid">
            <?php while ($book = $featured_books->fetch_assoc()): ?>
                <div class="book-card">
                    <span class="badge badge-success"><?php echo htmlspecialchars($book['category']); ?></span>
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <div class="author">by <?php echo htmlspecialchars($book['authors']); ?></div>
                    <div class="price">$<?php echo number_format($book['selling_price'], 2); ?></div>
                    <div class="stock">✓ In Stock (<?php echo $book['quantity_in_stock']; ?> available)</div>
                    <small style="color: #666; display: block; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($book['publisher_name']); ?> • <?php echo $book['publication_year']; ?>
                    </small>
                    <form method="POST" action="add_to_cart.php" style="display: flex; gap: 10px;">
                        <input type="hidden" name="isbn" value="<?php echo $book['isbn']; ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $book['quantity_in_stock']; ?>" 
                               style="width: 70px; padding: 8px; border: 2px solid #e1e8ed; border-radius: 5px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 8px;">
                            Add to Cart
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($featured_books->num_rows == 0): ?>
            <div style="padding: 30px; text-align: center; color: #666;">
                <p>No books available at the moment. Check back later!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?>