<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
    session_start();
    session_regenerate_id(true);
    $_SESSION['userId'] = $user['id'];
    $_SESSION['role']   = $user['role'];

    unset($user['password']);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user'    => [
            '_id'      => (string)$user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role']
        ]
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid username/email or password']);
}
$stmt->close();
$conn->close();
?>