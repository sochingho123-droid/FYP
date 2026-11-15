<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/auth.php';

isLoggedIn(); 

session_unset();
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

echo json_encode(['success' => true, 'message' => 'Logout successful']);
?>