<?php 
    session_start()
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - MMedication</title>
    <!-- 引入您的主 CSS 文件 -->
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f9fa; /* 与您登录页一致的极淡灰色背景 */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 顶部绿色导航栏 */
        .navbar {
            background-color: #2e7d32; /* 标志性的深绿色 */
            padding: 12px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar .logo {
            color: #ffffff;
            font-weight: bold;
            font-size: 20px;
        }

        .navbar .nav-links {
            display: flex;
            gap: 15px;
        }

        .navbar .nav-btn {
            color: #ffffff;
            text-decoration: none;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 14px;
            transition: background 0.2s;
        }

        .navbar .nav-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* 居中对齐容器 */
        .auth-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        /* 核心白卡片 */
        .auth-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); /* 淡淡的四周投影 */
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }

        .auth-card h2 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 30px;
            color: #333333;
            font-size: 24px;
            font-weight: 500;
        }

        /* 表单输入组 */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            color: #666666;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cccccc;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        /* 输入框聚焦时变成绿色边框 */
        .form-group input:focus {
            outline: none;
            border-color: #2e7d32;
        }

        /* 全宽深绿色注册按钮 */
        .btn-submit {
            width: 100%;
            background-color: #2e7d32;
            color: white;
            border: none;
            padding: 12px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }

        .btn-submit:hover {
            background-color: #215c24; /* 悬停时加深绿色 */
        }

        /* 底部跳转提示语 */
        .auth-footer {
            text-align: center;
            margin-top: 25px;
        }

        .auth-footer a {
            color: #888888;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.2s;
        }

        .auth-footer a:hover {
            color: #2e7d32;
            text-decoration: underline;
        }

        /* 注册失败/错误信息提示 */
        .error-msg {
            color: #d32f2f;
            font-size: 14px;
            text-align: center;
            margin-top: 15px;
            margin-bottom: 0;
        }
    </style>
</head>
<body>

    <!-- 顶部导航栏 (与登录页完全相同) -->
    <header class="navbar">
        <div href="/" class="logo">MMedication</div>
        <div class="nav-links">
            <a href="/" class="nav-btn">shop</a>
            <a href="/cart" class="nav-btn">cart</a>
        </div>
    </header>

    <!-- 注册主卡片区域 -->
    <main class="auth-container">
        <div class="auth-card">
            <h2>Create Account</h2>
            
        
            <form action="/creatAcc/create" method="POST">
                
                <div class="form-group">
                    <label for="username">USERNAME</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="email">EMAIL ADDRESS</label>
                    <input type="text" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">PASSWORD</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">CONFIRM PASSWORD</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="••••••••" required>
                </div>

                <!-- 注册按钮 -->
                <button type="submit" class="btn-submit">Create</button>
                
    
                <div class="auth-footer">
                    <a href="/">Already have an account? Sign In</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>