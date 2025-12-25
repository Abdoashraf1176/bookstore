<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone_number']);
    $address = sanitize($_POST['shipping_address']);
    $new_pass = $_POST['new_password'];

    // Update basic info
    $sql = "UPDATE users SET first_name=?, last_name=?, email=?, phone_number=?, shipping_address=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $user_id);

    if ($stmt->execute()) {
        // Update session data
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $message = "Profile updated successfully!";

        // Update password if provided
        if (!empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $p_stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $p_stmt->bind_param("si", $hash, $user_id);
            $p_stmt->execute();
            $message .= " Password changed.";
        }
    } else {
        $error = "Update failed: " . $conn->error;
    }
}

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>My Profile</h1>
            <nav>
                <a href="customer_dashboard.php">Home</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>

        <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
        <?php if ($error) echo "<div class='alert alert-error'>$error</div>"; ?>

        <div class="form-container">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Shipping Address</label>
                    <textarea name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['shipping_address']); ?></textarea>
                </div>

                <div class="form-group" style="border-top: 1px solid #eee; padding-top: 20px;">
                    <label>New Password (leave blank to keep current)</label>
                    <input type="password" name="new_password" minlength="6">
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</body>

</html>