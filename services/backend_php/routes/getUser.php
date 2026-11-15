<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

isLoggedIn(); 

$userId = $_SESSION['userId'];

$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'loggedIn' => false, 'message' => 'User not found']);
    exit;
}

echo json_encode([
    'success'  => true,
    'loggedIn' => true,
    'user'     => [
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $user['role']
    ]
]);

$stmt->close();
$conn->close();
?>