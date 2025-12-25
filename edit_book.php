<?php
require_once 'config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$error = '';
$book = null;

// 1. Search Logic (Requirement 2a & 5)
if (isset($_GET['isbn']) || isset($_POST['search_isbn'])) {
    $isbn = isset($_GET['isbn']) ? $_GET['isbn'] : $_POST['search_isbn'];
    $stmt = $conn->prepare("SELECT b.*, p.name as publisher_name FROM books b 
                            JOIN publishers p ON b.publisher_id = p.publisher_id 
                            WHERE b.isbn = ?");
    $stmt->bind_param("s", $isbn);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
    } else {
        $error = "Book not found!";
    }
}

// 2. Update Logic (Requirement 2b & 2c)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $isbn = $_POST['isbn'];
    $title = sanitize($_POST['title']);
    $price = floatval($_POST['selling_price']);
    $quantity = intval($_POST['quantity_in_stock']);
    $threshold = intval($_POST['threshold_quantity']);

    try {
        $update_stmt = $conn->prepare("UPDATE books SET title = ?, selling_price = ?, quantity_in_stock = ?, threshold_quantity = ? WHERE isbn = ?");
        $update_stmt->bind_param("sdiis", $title, $price, $quantity, $threshold, $isbn);
        
        if ($update_stmt->execute()) {
            $message = "Book updated successfully!";
            // Refresh local data
            $book['title'] = $title;
            $book['selling_price'] = $price;
            $book['quantity_in_stock'] = $quantity;
            $book['threshold_quantity'] = $threshold;
        }
    } catch (mysqli_sql_exception $e) {
        // This will catch the Trigger error if quantity becomes negative (Requirement 2c)
        $error = "Update Failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book - Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Books</h1>
            <nav><a href="admin_dashboard.php">Dashboard</a></nav>
        </div>

        <div style="padding: 30px;">
            <h2>Search & Edit Book</h2>
            
            <form method="POST" style="margin: 20px 0; display: flex; gap: 10px;">
                <input type="text" name="search_isbn" placeholder="Enter ISBN to search..." required>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <?php if ($message): ?> <div class="alert alert-success"><?php echo $message; ?></div> <?php endif; ?>
            <?php if ($error): ?> <div class="alert alert-error"><?php echo $error; ?></div> <?php endif; ?>

            <?php if ($book): ?>
            <form method="POST">
                <input type="hidden" name="isbn" value="<?php echo $book['isbn']; ?>">
                <div class="form-group">
                    <label>Book Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="selling_price" value="<?php echo $book['selling_price']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Quantity in Stock</label>
                    <input type="number" name="quantity_in_stock" value="<?php echo $book['quantity_in_stock']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Reorder Threshold</label>
                    <input type="number" name="threshold_quantity" value="<?php echo $book['threshold_quantity']; ?>" required>
                </div>
                <button type="submit" name="update_book" class="btn btn-primary">Save Changes</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>