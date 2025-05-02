<?php
session_start();
// If user is already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Check for login error messages
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="background-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <h1>POS Inventory System</h1>
            <p>Sign in to access your dashboard</p>
        </div>
        
        <?php if($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form class="login-form" action="auth.php" method="post" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <i class="fas fa-user form-icon"></i>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <i class="fas fa-lock form-icon"></i>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>
            
            <button type="submit">Sign In</button>
        </form>
        
        <div class="login-footer">
            <p>Forgot password? <a href="reset-password">Reset Password</a></p>
            <p style="margin-top: 15px;">© 2025 POS Inventory System</p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (username === '' || password === '') {
                event.preventDefault();
                
                // Create or update error message
                let errorMessage = document.querySelector('.error-message');
                if (!errorMessage) {
                    errorMessage = document.createElement('div');
                    errorMessage.className = 'error-message';
                    
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-exclamation-circle';
                    errorMessage.appendChild(icon);
                    
                    const text = document.createTextNode(' Please enter both username and password.');
                    errorMessage.appendChild(text);
                    
                    const loginHeader = document.querySelector('.login-header');
                    loginHeader.insertAdjacentElement('afterend', errorMessage);
                }
                
                // Animate the error fields
                if (username === '') {
                    document.getElementById('username').style.borderColor = '#e74c3c';
                }
                if (password === '') {
                    document.getElementById('password').style.borderColor = '#e74c3c';
                }
            }
        });
        
        // Reset border color when user starts typing
        document.getElementById('username').addEventListener('input', function() {
            this.style.borderColor = '';
        });
        
        document.getElementById('password').addEventListener('input', function() {
            this.style.borderColor = '';
        });
    </script>
</body>
</html>
