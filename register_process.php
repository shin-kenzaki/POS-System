<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['register_error'] = "All fields are required";
        header("Location: register.php");
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Invalid email format";
        header("Location: register.php");
        exit();
    }
    
    // Check password strength
    if (strlen($password) < 8 || !preg_match('/\d/', $password)) {
        $_SESSION['register_error'] = "Password must be at least 8 characters with at least one number";
        header("Location: register.php");
        exit();
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "Passwords do not match";
        header("Location: register.php");
        exit();
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "Username already exists";
        header("Location: register.php");
        exit();
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "Email already exists";
        header("Location: register.php");
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        $_SESSION['login_success'] = "Account created successfully! Please sign in.";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['register_error'] = "Error creating account: " . $conn->error;
        header("Location: register.php");
        exit();
    }
} else {
    // If not POST request, redirect to registration form
    header("Location: register.php");
    exit();
}
