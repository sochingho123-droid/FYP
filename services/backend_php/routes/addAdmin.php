<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

isAdmin(); 

$input    = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$email    = strtolower(trim($input['email'] ?? ''));

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit;
}
$stmt->close();

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}
$stmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
$stmt->bind_param("sss", $username, $hashedPassword, $email);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Admin created successfully', 'adminId' => (string)$stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

$stmt->close();
$conn->close();
?>