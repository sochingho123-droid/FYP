<?php 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 權限檢查：必須為 admin 角色
    $currentRole = $_SESSION['role'] ?? 'guest';
    if ($currentRole !== 'admin') {
        header("Location: /");
        exit();
    }

    // 當前登入用戶資訊
    $currentUserId = $_SESSION['logged_in_user'] ?? '';
    $currentUsername = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <link rel="stylesheet" href="/public/1.css">
    <style>
        body { padding: 20px; }
        .admin-container { width: 100%; max-width: 800px; margin: 0 auto; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
        .admin-nav { display: flex; gap: 12px; }
        .admin-nav a { color: #0969da; text-decoration: none; font-size: 14px; }
        .admin-nav a:hover { text-decoration: underline; }
        .search-bar { display: flex; gap: 8px; margin-bottom: 16px; }
        .search-bar input { flex: 1; padding: 8px; border: 1px solid #d0d7de; border-radius: 4px; }
        .search-bar button { width: auto; padding: 6px 12px; white-space: nowrap; }
        .data-table-container { border: 1px solid #d0d7de; border-radius: 6px; overflow: hidden; margin-top: 12px; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .data-table th { background-color: #f6f8fa; color: #1f2328; font-weight: 600; padding: 10px 12px; border-bottom: 1px solid #d0d7de; }
        .data-table td { padding: 10px 12px; color: #1f2328; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover { background-color: #f6f8fa; }
        .role-badge { display: inline-block; padding: 2px 8px; font-size: 12px; font-weight: 600; border-radius: 12px; text-transform: capitalize; }
        .role-admin { background-color: #ddf4ff; color: #0969da; border: 1px solid rgba(54, 150, 250, 0.2); }
        .role-user { background-color: #f6f8fa; color: #57606a; border: 1px solid #d0d7de; }
        .action-btn { padding: 4px 10px; font-size: 12px; width: auto; margin-top: 0; cursor: pointer; }
        .text-muted { color: #57606a; font-size: 12px; font-style: italic; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1 class="login-title" style="text-align: left; margin-bottom: 4px;">User Management</h1>
                <p class="form-hint">Logged in as: <strong><?= htmlspecialchars($currentUsername) ?></strong> (<?= htmlspecialchars($currentRole) ?>)</p>
            </div>
            <div class="admin-nav">
                <a href="/addAdmin">+ Create Admin</a>
                <a href="/usd">Back to Profile</a>
            </div>
        </div>

        <hr style="margin: 16px 0 24px 0;">

        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search by User ID, Username, or Email...">
            <button type="button" id="searchBtn" class="btn-primary">Search</button>
            <button type="button" id="resetBtn" class="btn-secondary">Reset</button>
        </div>

        <div id="msgBox" role="alert" style="display: none;"></div>

        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr>
                        <td colspan="5" style="text-align: center; color: #57606a; padding: 24px;">Loading users…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function() {
            'use strict';
            const currentUserId = "<?= (string)$currentUserId ?>";
            const currentUsername = "<?= htmlspecialchars($currentUsername) ?>";

            const searchInput = document.getElementById('searchInput');
            const searchBtn = document.getElementById('searchBtn');
            const resetBtn = document.getElementById('resetBtn');
            const userTableBody = document.getElementById('userTableBody');
            const msgBox = document.getElementById('msgBox');

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

            function isSelf(user) {
                const userId = String(user._id || user.id || '');
                return userId === currentUserId || (currentUsername !== '' && user.username === currentUsername);
            }

            function renderUsers(users) {
                if (!Array.isArray(users) || users.length === 0) {
                    userTableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #57606a; padding: 24px;">No users found.</td></tr>';
                    return;
                }

                userTableBody.innerHTML = users.map(user => {
                    const id = user._id ?? user.id ?? '';
                    const username = user.username ?? '';
                    const email = user.email ? user.email : 'N/A';
                    const role = user.role === 'admin' ? 'admin' : 'user';
                    const roleClass = role === 'admin' ? 'role-admin' : 'role-user';

                    const actionHtml = isSelf(user) 
                        ? '<span class="text-muted">You</span>'
                        : `<button type="button" class="btn-danger action-btn" data-id="${id}" data-name="${username}">Delete</button>`;

                    return `
                        <tr>
                            <td style="font-family: monospace; font-size: 13px;">${id}</td>
                            <td><strong>${username}</strong></td>
                            <td>${email}</td>
                            <td><span class="role-badge ${roleClass}">${role}</span></td>
                            <td style="text-align: right;">${actionHtml}</td>
                        </tr>
                    `;
                }).join('');

                // 綁定刪除按鈕事件
                document.querySelectorAll('.action-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.getAttribute('data-id');
                        const name = btn.getAttribute('data-name');
                        deleteUser(id, name);
                    });
                });
            }

            async function fetchUsers(keyword = '') {
                clearMessage();
                try {
                    const res = await fetch('/searchUser', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ keyword })
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        renderUsers(data.users);
                    } else if (res.status === 401 || res.status === 403) {
                        showMessage('error', data.message || 'Access denied. Please sign in as an administrator.');
                    } else {
                        showMessage('error', data.message || 'Failed to fetch users.');
                    }
                } catch (err) {
                    showMessage('error', 'System error while fetching users.');
                }
            }

            async function deleteUser(id, displayName) {
                const name = displayName || id || 'this user';
                if (!window.confirm(`Are you sure you want to delete user "${name}"?`)) return;

                clearMessage();

                try {
                    const res = await fetch(`/deleteUser?id=${encodeURIComponent(id)}`, {
                        method: 'POST'
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        const successMsg = data.message || `User "${name}" deleted successfully.`;
                        await fetchUsers(searchInput.value.trim());
                        showMessage('success', successMsg);
                    } else {
                        showMessage('error', data.message || 'Delete user failed.');
                    }
                } catch (err) {
                    showMessage('error', 'System error during deletion.');
                }
            }

            searchBtn.addEventListener('click', () => fetchUsers(searchInput.value.trim()));
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    fetchUsers(searchInput.value.trim());
                }
            });
            resetBtn.addEventListener('click', () => {
                searchInput.value = '';
                fetchUsers('');
            });

            // 初始載入數據
            fetchUsers('');
        })();
    </script>
</body>
</html>