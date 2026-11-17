<?php 
    session_start();

    session_destroy();
    session_unset();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MMedication</title>
    <!-- Add your public CSS link here if needed -->
    <!-- <link rel="stylesheet" href="YOUR_PUBLIC_CSS_URL_HERE.css"> -->
    <style>
        /* Base styles matching the storefront brand */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f7f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Header Navigation Bar */
        .navbar {
            background-color: #2e7d32;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 40px;
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 100%;
        }

        .brand-logo {
            font-weight: 700;
            font-size: 22px;
            letter-spacing: 0.5px;
            text-decoration: none;
            color: white;
        }

        .nav-actions {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            text-transform: lowercase;
            text-decoration: none;
            transition: background 0.2s;
        }

        .nav-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Central Login Component Container */
        .login-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Login Card Layout (Matches product card design framework) */
        .login-card {
            background: #ffffff;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            width: 100%;
            max-width: 400px;
            padding: 35px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .login-title {
            color: #1a365d; /* Dark Blue Slate from main page heading */
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
        }

        /* Form Input Field Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            outline: none;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.15);
        }

        /* Utility links row */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            color: #4a5568;
        }

        .forgot-pass {
            color: #2e7d32;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-pass:hover {
            text-decoration: underline;
        }

        /* Submission Action Elements */
        .login-submit-btn {
            width: 100%;
            background-color: #2e7d32;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .login-submit-btn:hover {
            background-color: #1b5e20;
        }

        .register-prompt {
            text-align: center;
            margin-top: 25px;
            font-size: 13px;
            color: #718096;
        }

        .register-link {
            color: #2e7d32;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link:hover {
            text-decoration: underline;
        }

        /* Quick handling for smaller viewports */
        @media (max-width: 480px) {
            .navbar {
                padding: 10px 20px;
            }
            .login-card {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation View -->
    <header class="navbar">
        <a href="/" class="brand-logo">MMedication</a>
        <div class="nav-actions">
            <a href="/" class="nav-btn">shop</a>
            <a href="/cart" class="nav-btn">cart</a>
        </div>
    </header>

    <!-- Content Layout Wrapper -->
    <main class="login-container">
        
        <!-- Login Form Element Box -->
        <div class="login-card">
            <h1 class="login-title">Sign In</h1>
            
            <form action="/login-endpoint" method="POST">
                
                <div class="form-group">
                    <label for="username" class="form-label">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-input" required placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required placeholder="••••••••">
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="/forgetPassword" class="forgot-pass">Forgot password?</a>
                </div>

                <button type="submit" class="login-submit-btn">Login</button>
                <div>
                    <?php 

                    ?>
                </div>
                <div class="register-prompt">
                    Don't have an account? <a href="/creatAcc" class="register-link">Create one</a>
                </div>

            </form>
        </div>

    </main>

</body>
</html>