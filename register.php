<?php
session_start();
// If user is already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Check for registration error messages
$error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : '';
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .login-container {
            width: 450px;
            max-width: 95%;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            padding-left: 5px;
        }
        .password-requirements ul {
            padding-left: 20px;
            margin: 5px 0;
        }
        .login-form button {
            margin-top: 10px;
        }
    </style>
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
            <h1>Create Account</h1>
            <p>Sign up to access the POS Inventory System</p>
        </div>
        
        <?php if($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form class="login-form" action="register_process.php" method="post" id="registerForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required>
                <i class="fas fa-user form-icon"></i>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <i class="fas fa-envelope form-icon"></i>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password" required>
                <i class="fas fa-lock form-icon"></i>
                <div class="password-requirements">
                    Password must contain:
                    <ul>
                        <li>At least 8 characters</li>
                        <li>At least one number</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                <i class="fas fa-lock form-icon"></i>
            </div>
            
            <button type="submit">Create Account</button>
        </form>
        
        <div class="login-footer">
            <p>Already have an account? <a href="index.php">Sign In</a></p>
            <p style="margin-top: 15px;">© 2025 POS Inventory System</p>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirm_password = document.getElementById('confirm_password').value.trim();
            
            let hasError = false;
            let errorMessage = '';
            
            // Reset border colors
            document.getElementById('username').style.borderColor = '';
            document.getElementById('email').style.borderColor = '';
            document.getElementById('password').style.borderColor = '';
            document.getElementById('confirm_password').style.borderColor = '';
            
            // Check if fields are empty
            if (username === '') {
                document.getElementById('username').style.borderColor = '#e74c3c';
                errorMessage = 'All fields are required';
                hasError = true;
            }
            
            if (email === '') {
                document.getElementById('email').style.borderColor = '#e74c3c';
                errorMessage = 'All fields are required';
                hasError = true;
            }
            
            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email !== '' && !emailRegex.test(email)) {
                document.getElementById('email').style.borderColor = '#e74c3c';
                errorMessage = 'Please enter a valid email address';
                hasError = true;
            }
            
            // Validate password
            const passwordRegex = /^(?=.*\d).{8,}$/;
            if (password !== '' && !passwordRegex.test(password)) {
                document.getElementById('password').style.borderColor = '#e74c3c';
                errorMessage = 'Password must be at least 8 characters with at least one number';
                hasError = true;
            }
            
            // Check if passwords match
            if (password !== confirm_password) {
                document.getElementById('password').style.borderColor = '#e74c3c';
                document.getElementById('confirm_password').style.borderColor = '#e74c3c';
                errorMessage = 'Passwords do not match';
                hasError = true;
            }
            
            if (hasError) {
                event.preventDefault();
                
                // Display error message
                let errorElement = document.querySelector('.error-message');
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'error-message';
                    
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-exclamation-circle';
                    errorElement.appendChild(icon);
                    
                    const text = document.createTextNode(' ' + errorMessage);
                    errorElement.appendChild(text);
                    
                    const loginHeader = document.querySelector('.login-header');
                    loginHeader.insertAdjacentElement('afterend', errorElement);
                } else {
                    // Update existing error message
                    errorElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + errorMessage;
                }
            }
        });
        
        // Reset border color when user starts typing
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                this.style.borderColor = '';
            });
        });
    </script>
</body>
</html>
