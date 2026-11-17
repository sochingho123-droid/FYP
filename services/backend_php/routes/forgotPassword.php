<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($input['email'] ?? ''));

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No account found with this email']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Password reset request accepted. (Simulated verification link/code sent!)'
]);

$stmt->close();
$conn->close();
?>