<?php 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: /");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account</title>
    <link rel="stylesheet" href="/public/1.css">
</head>
<body>
    <div class="login-container">
        <div class="logo-placeholder"><!-- logo --></div>
        <h1 class="login-title">Create Admin Account</h1>
        
        <div id="msgBox" role="alert" style="display: none;"></div>

        <form id="addAdminForm" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
                <span class="form-hint">At least 8 characters, containing uppercase &amp; lowercase letters, numbers, and special characters (!@#$%^&*_).</span>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required autocomplete="new-password">
            </div>

            <button type="submit" id="submitBtn" class="btn-primary">Add Admin</button>
        </form>

        <div class="signup-wrapper" style="margin-top: 16px; text-align: center;">
            <a href="/uselist">Back to User List</a>
        </div>
    </div>

    <script>
        (function() {
            'use strict';
            const PASSWORD_PATTERN = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\_])[A-Za-z\d!@#$%^&*\_]{8,}$/;

            const form = document.getElementById('addAdminForm');
            const msgBox = document.getElementById('msgBox');
            const submitBtn = document.getElementById('submitBtn');

            function showMessage(type, text) {
                msgBox.innerText = text;
                msgBox.className = type === 'success' ? 'success-msg' : 'error-msg';
                msgBox.style.display = 'block';
            }

            function clearMessage() {
                msgBox.innerText = '';
                msgBox.className = '';
                msgBox.style.display = 'none';
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearMessage();

                const username = document.getElementById('username').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (!PASSWORD_PATTERN.test(password)) {
                    showMessage('error', 'Password must be at least 8 characters long and contain uppercase, lowercase, numbers, and special characters (!@#$%^&*_).');
                    return;
                }

                if (password !== confirmPassword) {
                    showMessage('error', 'Passwords do not match!');
                    return;
                }

                submitBtn.disabled = true;

                try {
                    const res = await fetch('/addAdmin', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username, email, password, confirmPassword })
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        showMessage('success', data.message || 'Admin account created successfully!');
                        form.reset();
                    } else if (res.status === 400) {
                        showMessage('error', data.message || 'Invalid input or user already exists.');
                    } else if (res.status === 403) {
                        showMessage('error', data.message || 'Access denied. Admin permissions required.');
                    } else if (res.status >= 500) {
                        showMessage('error', data.message || 'The authentication service is unavailable. Please try again later.');
                    } else {
                        showMessage('error', data.message || 'Failed to add admin.');
                    }
                } catch (err) {
                    showMessage('error', 'System connection error, please try again later.');
                } finally {
                    submitBtn.disabled = false;
                }
            });
        })();
    </script>
</body>
</html>