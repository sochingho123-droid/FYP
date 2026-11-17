<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    if (!isset($_SESSION['userId'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Please log in.']);
        exit;
    }
}

function isAdmin() {
    if (!isset($_SESSION['userId']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin role required.']);
        exit;
    }
}

function isUser() {
    if (!isset($_SESSION['userId']) || ($_SESSION['role'] ?? '') !== 'user') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. User role required.']);
        exit;
    }
}
?>