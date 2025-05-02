<?php
session_start();
require_once 'db.php';

// Define session timeout in seconds (30 minutes)
$session_timeout = 1800; 

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['is_authenticated'] === true;
}

// Function to check for session timeout
function checkSessionTimeout() {
    global $session_timeout;
    
    if (isLoggedIn()) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
            // Session has expired
            doLogout();
            return false;
        } else {
            // Update last activity time
            $_SESSION['last_activity'] = time();
            return true;
        }
    }
    return false;
}

// Function to handle logout
function doLogout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    
    session_destroy();
}

// Check for "remember me" cookie and auto-login
function checkRememberMe($conn) {
    if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
        $token = filter_input(INPUT_COOKIE, 'remember_token', FILTER_SANITIZE_STRING);
        
        if (!empty($token)) {
            try {
                $stmt = $conn->prepare("SELECT user_id, username, full_name, role, is_active FROM users WHERE remember_token = ? AND is_active = 1");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['is_authenticated'] = true;
                    $_SESSION['last_activity'] = time();
                    
                    // Generate a new remember token for security
                    $new_token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $new_token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
                    
                    // Update the token in database
                    $update_stmt = $conn->prepare("UPDATE users SET remember_token = ?, last_login = NOW() WHERE user_id = ?");
                    $update_stmt->bind_param("si", $new_token, $user['user_id']);
                    $update_stmt->execute();
                    
                    return true;
                }
            } catch (Exception $e) {
                error_log("Remember me error: " . $e->getMessage());
            }
        }
    }
    return false;
}

// Function to check user permissions
function hasPermission($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // If required role is an array, check if user's role is in the array
    if (is_array($requiredRole)) {
        return in_array($_SESSION['role'], $requiredRole);
    }
    
    // Admin has all permissions
    if ($_SESSION['role'] === 'admin') {
        return true;
    }
    
    // Check specific role
    return $_SESSION['role'] === $requiredRole;
}

// Check if user is remembered or session is active
if (!isLoggedIn()) {
    checkRememberMe($conn);
} else {
    checkSessionTimeout();
}
