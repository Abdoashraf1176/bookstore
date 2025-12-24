<?php
// Start session first
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306'); // MySQL default port
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookstore_system');

// Create connection function
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin';
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Redirect if already logged in
if (isLoggedIn()) {
    // if (isAdmin()) {
    //     header("Location: admin_dashboard.php");
    // } else {
    //     header("Location: customer_dashboard.php");
    // }
            header("Location: admin_dashboard.php");

    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT user_id, username, password, user_type, first_name, last_name FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                
                // Redirect based on user type
                // if ($user['user_type'] === 'Admin') {
                //     header("Location: admin_dashboard.php");
                // } else {
                //     header("Location: customer_dashboard.php");
                // }
                            header("Location: admin_dashboard.php");

                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
        
        $conn->close();
    } else {
        $error = "Please enter both username and password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bookstore System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 450px;
            margin: 100px auto;
        }
        
        .login-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e8ed;
        }
        
        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>📚 Bookstore System</h1>
                <p>Login to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin: 20px 30px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="login-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                </form>
                
                <div class="signup-link">
                    Don't have an account? <a href="register.php">Sign up here</a>
                </div>
                
                <div class="signup-link" style="border-top: none; padding-top: 10px;">
                    <small style="color: #666;">
                        Demo: Username: <strong>admin</strong>, Password: <strong>admin123</strong>
                    </small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>