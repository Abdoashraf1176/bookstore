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

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check authentication
if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $isbn = sanitize($_POST['isbn']);
    $title = sanitize($_POST['title']);
    $publisher_id = intval($_POST['publisher_id']);
    $publication_year = intval($_POST['publication_year']);
    $selling_price = floatval($_POST['selling_price']);
    $category = sanitize($_POST['category']);
    $quantity_in_stock = intval($_POST['quantity_in_stock']);
    $threshold_quantity = intval($_POST['threshold_quantity']);
    $authors = isset($_POST['authors']) ? $_POST['authors'] : [];
    
    // Validation
    $errors = [];
    
    if (empty($isbn) || !preg_match('/^\d{13}$/', $isbn)) {
        $errors[] = "ISBN must be a 13-digit number";
    }
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if ($publisher_id <= 0) {
        $errors[] = "Please select a valid publisher";
    }
    
    if ($publication_year < 1000 || $publication_year > date('Y')) {
        $errors[] = "Invalid publication year";
    }
    
    if ($selling_price <= 0) {
        $errors[] = "Selling price must be greater than 0";
    }
    
    if (!in_array($category, ['Science', 'Art', 'Religion', 'History', 'Geography'])) {
        $errors[] = "Invalid category";
    }
    
    if ($quantity_in_stock < 0) {
        $errors[] = "Quantity in stock cannot be negative";
    }
    
    if ($threshold_quantity <= 0) {
        $errors[] = "Threshold quantity must be greater than 0";
    }
    
    if (empty($authors)) {
        $errors[] = "At least one author is required";
    }
    
    if (empty($errors)) {
        // Check if ISBN already exists
        $check_stmt = $conn->prepare("SELECT isbn FROM books WHERE isbn = ?");
        $check_stmt->bind_param("s", $isbn);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "A book with this ISBN already exists";
        } else {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert book
                $stmt = $conn->prepare("INSERT INTO books (isbn, title, publisher_id, publication_year, selling_price, category, quantity_in_stock, threshold_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiidsii", $isbn, $title, $publisher_id, $publication_year, $selling_price, $category, $quantity_in_stock, $threshold_quantity);
                $stmt->execute();
                
                // Insert authors
                $author_stmt = $conn->prepare("INSERT INTO book_authors (isbn, author_id) VALUES (?, ?)");
                foreach ($authors as $author_id) {
                    $author_id = intval($author_id);
                    $author_stmt->bind_param("si", $isbn, $author_id);
                    $author_stmt->execute();
                }
                
                // Commit transaction
                $conn->commit();
                $message = "Book added successfully!";
                
                // Clear form
                $_POST = [];
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error adding book: " . $e->getMessage();
            }
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Fetch publishers for dropdown
$publishers = $conn->query("SELECT publisher_id, name FROM publishers ORDER BY name");

// Fetch authors for selection
$authors_list = $conn->query("SELECT author_id, name FROM authors ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book - Bookstore Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Add New Book</h1>
            <nav>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="" id="addBookForm">
                <div class="form-group">
                    <label for="isbn">ISBN (13 digits) *</label>
                    <input type="text" id="isbn" name="isbn" required pattern="\d{13}" 
                           placeholder="9780123456789" value="<?php echo isset($_POST['isbn']) ? $_POST['isbn'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="title">Book Title *</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo isset($_POST['title']) ? $_POST['title'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="publisher_id">Publisher *</label>
                    <select id="publisher_id" name="publisher_id" required>
                        <option value="">Select Publisher</option>
                        <?php while ($pub = $publishers->fetch_assoc()): ?>
                            <option value="<?php echo $pub['publisher_id']; ?>"
                                <?php echo (isset($_POST['publisher_id']) && $_POST['publisher_id'] == $pub['publisher_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pub['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="publication_year">Publication Year *</label>
                        <input type="number" id="publication_year" name="publication_year" required 
                               min="1000" max="<?php echo date('Y'); ?>" 
                               value="<?php echo isset($_POST['publication_year']) ? $_POST['publication_year'] : date('Y'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="selling_price">Selling Price ($) *</label>
                        <input type="number" id="selling_price" name="selling_price" required 
                               step="0.01" min="0.01" 
                               value="<?php echo isset($_POST['selling_price']) ? $_POST['selling_price'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Science" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Science') ? 'selected' : ''; ?>>Science</option>
                        <option value="Art" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Art') ? 'selected' : ''; ?>>Art</option>
                        <option value="Religion" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Religion') ? 'selected' : ''; ?>>Religion</option>
                        <option value="History" <?php echo (isset($_POST['category']) && $_POST['category'] == 'History') ? 'selected' : ''; ?>>History</option>
                        <option value="Geography" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Geography') ? 'selected' : ''; ?>>Geography</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity_in_stock">Quantity in Stock *</label>
                        <input type="number" id="quantity_in_stock" name="quantity_in_stock" required 
                               min="0" value="<?php echo isset($_POST['quantity_in_stock']) ? $_POST['quantity_in_stock'] : '0'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="threshold_quantity">Threshold Quantity *</label>
                        <input type="number" id="threshold_quantity" name="threshold_quantity" required 
                               min="1" value="<?php echo isset($_POST['threshold_quantity']) ? $_POST['threshold_quantity'] : '10'; ?>">
                        <small>Minimum stock level before automatic reorder</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Authors * (Select one or more)</label>
                    <div class="checkbox-group">
                        <?php while ($author = $authors_list->fetch_assoc()): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="authors[]" value="<?php echo $author['author_id']; ?>"
                                    <?php echo (isset($_POST['authors']) && in_array($author['author_id'], $_POST['authors'])) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($author['name']); ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Book</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>