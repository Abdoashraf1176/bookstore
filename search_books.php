<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';

$sql = "SELECT b.*, p.name as publisher_name, GROUP_CONCAT(a.name SEPARATOR ', ') as authors 
        FROM books b 
        LEFT JOIN publishers p ON b.publisher_id = p.publisher_id 
        LEFT JOIN book_authors ba ON b.isbn = ba.isbn 
        LEFT JOIN authors a ON ba.author_id = a.author_id 
        WHERE 1=1";

if ($search) {
    $sql .= " AND (b.title LIKE '%$search%' OR b.isbn LIKE '%$search%' OR a.name LIKE '%$search%' OR p.name LIKE '%$search%')";
}
if ($category) {
    $sql .= " AND b.category = '$category'";
}

$sql .= " GROUP BY b.isbn";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Search Books</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Search Books</h1>
            <nav>
                <a href="customer_dashboard.php">Home</a>
                <a href="shopping_cart.php">Cart</a>
            </nav>
        </div>

        <div class="search-bar">
            <form method="GET">
                <input type="text" name="q" placeholder="Search by Title, ISBN, or Author..." value="<?php echo $search; ?>">
                <select name="category" style="padding: 12px; border: 2px solid #e1e8ed; border-radius: 5px;">
                    <option value="">All Categories</option>
                    <option value="Science" <?php if ($category == 'Science') echo 'selected'; ?>>Science</option>
                    <option value="Art" <?php if ($category == 'Art') echo 'selected'; ?>>Art</option>
                    <option value="Religion" <?php if ($category == 'Religion') echo 'selected'; ?>>Religion</option>
                    <option value="History" <?php if ($category == 'History') echo 'selected'; ?>>History</option>
                    <option value="Geography" <?php if ($category == 'Geography') echo 'selected'; ?>>Geography</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author(s)</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                                    <small>ISBN: <?php echo $row['isbn']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['authors']); ?></td>
                                <td><span class="badge badge-success"><?php echo $row['category']; ?></span></td>
                                <td>$<?php echo $row['selling_price']; ?></td>
                                <td>
                                    <?php if ($row['quantity_in_stock'] > 0): ?>
                                        <form action="add_to_cart.php" method="POST">
                                            <input type="hidden" name="isbn" value="<?php echo $row['isbn']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">Add to Cart</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No books found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>