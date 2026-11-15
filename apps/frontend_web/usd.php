<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isLoggedIn = isset($_SESSION['logged_in_user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - MMedication</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        [hidden] { display: none !important; }

        body {
            background-color: #f7f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: #2e7d32;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 40px;
            color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
        }

        .brand-logo {
            font-weight: 700;
            font-size: 22px;
            letter-spacing: .5px;
            text-decoration: none;
            color: #fff;
        }

        .nav-btn {
            background-color: rgba(255, 255, 255, .2);
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            transition: background .2s;
        }

        .nav-btn:hover { background-color: rgba(255, 255, 255, .3); }

        .login-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .profile-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            width: 100%;
            max-width: 440px;
            padding: 35px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .05);
        }

        .login-title {
            color: #1a365d;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 22px;
            text-align: center;
        }

        .msg-container {
            font-size: 14px;
            text-align: center;
            min-height: 20px;
            margin-bottom: 14px;
            line-height: 1.4;
        }

        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            outline: none;
            font-size: 14px;
            transition: border-color .2s, background .2s;
        }

        .form-input:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, .15);
        }

        .form-input:disabled {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-group button,
        .btn-group a {
            flex: 1;
            width: auto;
        }

        .btn-primary,
        .btn-secondary,
        .btn-danger {
            border: none;
            border-radius: 4px;
            padding: 11px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }

        .btn-primary {
            background-color: #2e7d32;
            color: #fff;
            width: 100%;
        }

        .btn-primary:hover { background-color: #1b5e20; }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }

        .btn-secondary:hover { background-color: #cbd5e1; }

        .btn-danger {
            background-color: #dc2626;
            color: #fff;
            width: 100%;
        }

        .btn-danger:hover { background-color: #b91c1c; }

        button:disabled { opacity: .6; cursor: not-allowed; }

        .divider {
            border: 0;
            border-top: 1px solid #e2e8f0;
            margin: 24px 0;
        }

        .guest-box { text-align: center; line-height: 1.7; color: #4a5568; }
        .guest-box strong { color: #1a365d; }
        .profile-links { display: flex; flex-direction: column; gap: 10px; }
        .profile-links a { color: #2e7d32; text-decoration: none; font-size: 14px; }
        .profile-links a:hover { text-decoration: underline; }

        .loading { text-align: center; color: #718096; font-size: 14px; }

        @media (max-width: 480px) {
            .navbar { padding: 10px 20px; }
            .profile-card { padding: 25px 20px; }
        }
    </style>
</head>
<body>
 
    <header class="navbar">
        <a href="/" class="brand-logo">MMedication</a>
        <a href="/" class="nav-btn">shop</a>
    </header>

    <main class="login-container">
        <div class="profile-card">
            <h1 class="login-title">User Profile</h1>
            <div id="msg" class="msg-container" role="alert" aria-live="polite"></div>
            <div id="profileSection" <?= $isLoggedIn ? '' : 'hidden' ?>>
                <div id="loadingHint" class="loading">Loading profile…</div>

                <form id="profileForm" class="login-form" hidden>
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username"
                               class="form-input" value="" disabled required
                               autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email"
                               class="form-input" value="" disabled required
                               autocomplete="email">
                    </div>

                    <button type="button" id="editBtn" class="btn-secondary"
                            style="width:100%">Edit Profile</button>

                    <div id="actionBtns" class="btn-group" hidden>
                        <button type="submit" class="btn-primary">Save</button>
                        <button type="button" id="cancelBtn" class="btn-secondary">Cancel</button>
                    </div>
                </form>

                <hr class="divider">

                <div class="profile-links">
                    <button type="button" id="logoutBtn" class="btn-danger">Logout</button>
                </div>
            </div>
            <div id="guestSection" class="guest-box" <?= $isLoggedIn ? 'hidden' : '' ?>>
                <p>You are currently visiting as <strong>Guest</strong>.</p>
                <p>Please log in to manage your profile settings.</p>

                <div class="btn-group" style="margin-top:16px;">
                    <button type="button" class="btn-primary"
                            onclick="window.location.href='/'">Log In</button>
                    <button type="button" class="btn-secondary"
                            onclick="window.location.href='/creatAcc'">Sign Up</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function () {
            'use strict';
            const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;

            const msg            = document.getElementById('msg');
            const profileSection = document.getElementById('profileSection');
            const guestSection   = document.getElementById('guestSection');
            const loadingHint    = document.getElementById('loadingHint');
            const profileForm    = document.getElementById('profileForm');

            function showMessage(text, color) {
                msg.textContent = text || '';
                msg.style.color = color || '#333';
            }

            async function readJson(response) {
                try {
                    return await response.json();
                } catch (err) {
                    return {};
                }
            }

            function showGuest() {
                profileSection.hidden = true;
                guestSection.hidden = false;
            }

            async function loadProfile() {
                if (!IS_LOGGED_IN) {
                    showGuest();
                    return;
                }

                try {
                    const response = await fetch('/usd', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({})
                    });

                    const data = await readJson(response);
                    if (!response.ok || !data.loggedIn || !data.user) {
                        showGuest();
                        if (response.status >= 500) {
                            showMessage('Unable to load your profile. Please log in again.', 'red');
                        }
                        return;
                    }

                    const user = data.user;

                    document.getElementById('username').value = user.username || '';
                    document.getElementById('email').value    = user.email || '';

                    const adminLink = document.getElementById('adminLink');
                    if (adminLink && user.role === 'admin') {
                        adminLink.hidden = false;
                    }

                    loadingHint.hidden = true;
                    profileForm.hidden = false;
                } catch (err) {
                    showGuest();
                    showMessage('System connection error.', 'red');
                }
            }
            const usernameInput = document.getElementById('username');
            const emailInput    = document.getElementById('email');
            const editBtn       = document.getElementById('editBtn');
            const actionBtns    = document.getElementById('actionBtns');
            const cancelBtn     = document.getElementById('cancelBtn');

            let originalUsername = '';
            let originalEmail    = '';

            if (editBtn) {
                editBtn.addEventListener('click', function () {
                    originalUsername = usernameInput.value;
                    originalEmail    = emailInput.value;

                    usernameInput.disabled = false;
                    emailInput.disabled    = false;
                    editBtn.hidden         = true;
                    actionBtns.hidden      = false;
                    showMessage('', '');
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    usernameInput.value = originalUsername;
                    emailInput.value    = originalEmail;
                    usernameInput.disabled = true;
                    emailInput.disabled    = true;
                    actionBtns.hidden      = true;
                    editBtn.hidden         = false;
                    showMessage('', '');
                });
            }

            if (profileForm) {
                profileForm.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    showMessage('', '');

                    const submitButton = profileForm.querySelector('button[type="submit"]');
                    const username = usernameInput.value.trim();
                    const email    = emailInput.value.trim();

                    if (!username || !email) {
                        showMessage('Username and email cannot be empty.', 'red');
                        return;
                    }

                    if (submitButton) submitButton.disabled = true;

                    try {
                        const response = await fetch('/cusd', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ username: username, email: email })
                        });

                        const data = await readJson(response);

                        if (response.ok && data.success) {
                            originalUsername = username;
                            originalEmail    = email;

                            usernameInput.disabled = true;
                            emailInput.disabled    = true;
                            actionBtns.hidden      = true;
                            editBtn.hidden         = false;

                            showMessage(data.message || 'Profile updated successfully!', 'green');
                        } else {
                            showMessage(data.message || 'Update failed.', 'red');
                        }
                    } catch (err) {
                        showMessage('System connection error. Please try again.', 'red');
                    } finally {
                        if (submitButton) submitButton.disabled = false;
                    }
                });
            }

            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function () {
                    if (!window.confirm('Are you sure you want to logout?')) return;
                    window.location.href = '/logout';
                });
            }

            loadProfile();
        })();
    </script>
</body>
</html>
