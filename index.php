<?php
require_once 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Note App</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Note App</h1>
            <div class="tabs">
                <button class="tab-btn active" data-tab="login">Login</button>
                <button class="tab-btn" data-tab="signup">Sign Up</button>
            </div>
            <div id="login-form" class="tab-content active">
                <form id="loginForm">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                    <div id="login-message" class="message"></div>
                </form>
            </div>
            <div id="signup-form" class="tab-content">
                <form id="signupForm">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label for="new-username">Username</label>
                        <input type="text" id="new-username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="new-password">Password</label>
                        <input type="password" id="new-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Sign Up</button>
                    </div>
                    <div id="signup-message" class="message"></div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            const tabs = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    this.classList.add('active');
                    document.getElementById(`${this.dataset.tab}-form`).classList.add('active');
                });
            });
            
            // Login form submission
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const messageElement = document.getElementById('login-message');
                    
                    if (data.success) {
                        messageElement.textContent = data.message;
                        messageElement.className = 'message success';
                        
                        // Redirect to dashboard after successful login
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 1000);
                    } else {
                        messageElement.textContent = data.message;
                        messageElement.className = 'message error';
                    }
                })
                .catch(error => {
                    document.getElementById('login-message').textContent = 'An error occurred. Please try again.';
                    document.getElementById('login-message').className = 'message error';
                    console.error('Error:', error);
                });
            });
            
            // Signup form submission
            document.getElementById('signupForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const messageElement = document.getElementById('signup-message');
                    
                    if (data.success) {
                        messageElement.textContent = data.message;
                        messageElement.className = 'message success';
                        
                        // Switch to login tab after successful registration
                        setTimeout(() => {
                            document.querySelector('[data-tab="login"]').click();
                        }, 1500);
                    } else {
                        messageElement.textContent = data.message;
                        messageElement.className = 'message error';
                    }
                })
                .catch(error => {
                    document.getElementById('signup-message').textContent = 'An error occurred. Please try again.';
                    document.getElementById('signup-message').className = 'message error';
                    console.error('Error:', error);
                });
            });
        });
    </script>
</body>
</html>