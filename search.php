<?php
require_once 'config.php';

$conn = getDBConnection();

// Get Search Inputs
$search_text = isset($_GET['search_text']) ? sanitize($_GET['search_text']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Build Query to handle ISBN, Title, Category, Author, and Publisher (Req 5a, 5b)
$query = "SELECT b.*, p.name as publisher_name, GROUP_CONCAT(a.name SEPARATOR ', ') as authors
          FROM books b
          LEFT JOIN publishers p ON b.publisher_id = p.publisher_id
          LEFT JOIN book_authors ba ON b.isbn = ba.isbn
          LEFT JOIN authors a ON ba.author_id = a.author_id
          WHERE (b.title LIKE ? OR b.isbn = ? OR a.name LIKE ? OR p.name LIKE ?) ";

if (!empty($category)) {
    $query .= " AND b.category = ? ";
}

$query .= " GROUP BY b.isbn";

$stmt = $conn->prepare($query);
$search_param = "%$search_text%";

if (!empty($category)) {
    $stmt->bind_param("sssss", $search_param, $search_text, $search_param, $search_param, $category);
} else {
    $stmt->bind_param("ssss", $search_param, $search_text, $search_param, $search_param);
}

$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Books - Bookstore</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Search Books</h1>
            <nav>
                <a href="index.php">Home</a>
                <?php echo isAdmin() ? '<a href="admin_dashboard.php">Dashboard</a>' : '<a href="customer_dashboard.php">My Account</a>'; ?>
            </nav>
        </div>

        <div class="report-section" style="background: #f4f4f4; padding: 20px; border-radius: 8px;">
            <form method="GET" action="search.php">
                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <input type="text" name="search_text" placeholder="Search by Title, ISBN, Author or Publisher..." value="<?php echo $search_text; ?>" style="flex: 2; padding: 10px;">
                    
                    <select name="category" style="flex: 1; padding: 10px;">
                        <option value="">All Categories</option>
                        <option value="Science" <?php if($category == 'Science') echo 'selected'; ?>>Science</option>
                        <option value="Art" <?php if($category == 'Art') echo 'selected'; ?>>Art</option>
                        <option value="Religion" <?php if($category == 'Religion') echo 'selected'; ?>>Religion</option>
                        <option value="History" <?php if($category == 'History') echo 'selected'; ?>>History</option>
                        <option value="Geography" <?php if($category == 'Geography') echo 'selected'; ?>>Geography</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        <h3>Search Results (<?php echo $results->num_rows; ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Title & Author</th>
                    <th>Category</th>
                    <th>Publisher</th>
                    <th>Price</th>
                    <th>Availability</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $results->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['isbn']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                        <small>By: <?php echo htmlspecialchars($row['authors']); ?></small>
                    </td>
                    <td><?php echo $row['category']; ?></td>
                    <td><?php echo htmlspecialchars($row['publisher_name']); ?></td>
                    <td>$<?php echo number_format($row['selling_price'], 2); ?></td>
                    <td>
                        <?php if ($row['quantity_in_stock'] > 0): ?>
                            <span style="color: green;">Available (<?php echo $row['quantity_in_stock']; ?>)</span>
                        <?php else: ?>
                            <span style="color: red;">Out of Stock</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>