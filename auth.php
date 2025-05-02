<?php
session_start();
require_once 'db.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input and sanitize
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password']; // Don't sanitize password as it may contain special characters
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both username and password.";
        header("Location: index.php");
        exit();
    }
    
    try {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT user_id, username, password, full_name, role, is_active FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            if (!$user['is_active']) {
                $_SESSION['login_error'] = "Your account is inactive. Please contact an administrator.";
                header("Location: index.php");
                exit();
            }
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_regenerate_id(); // Prevent session fixation attacks
                
                // Store user data in session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_authenticated'] = true;
                $_SESSION['last_activity'] = time();
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32)); // Generate a secure token
                    
                    // Set cookie to expire in 30 days
                    setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
                    
                    // Store token in database (you would need to add a remember_token field to users table)
                    $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $token, $user['user_id']);
                    $stmt->execute();
                }
                
                // Update last login time
                $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $stmt->bind_param("i", $user['user_id']);
                $stmt->execute();
                
                // Redirect all users to the main dashboard for now
                // Later you can create role-specific directories and update these paths
                header("Location: dashboard.php");
                
                /* 
                // UNCOMMENT THIS SECTION WHEN ROLE-SPECIFIC DIRECTORIES ARE CREATED
                // Redirect based on user role
                switch ($user['role']) {
                    case 'admin':
                    case 'manager':
                        header("Location: admin/dashboard.php");
                        break;
                    case 'cashier':
                        header("Location: cashier/pos.php");
                        break;
                    case 'inventory':
                        header("Location: inventory/dashboard.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                */
                
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid username or password.";
            }
        } else {
            $_SESSION['login_error'] = "Invalid username or password.";
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['login_error'] = "A system error occurred. Please try again later.";
    }
    
    // Redirect back to login page if authentication failed
    header("Location: index.php");
    exit();
}
else {
    // If someone tries to access this file directly
    header("Location: index.php");
    exit();
}
