<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

$input       = json_decode(file_get_contents('php://input'), true);
$email       = strtolower(trim($input['email'] ?? ''));
$newPassword = $input['newPassword'] ?? '';

if (empty($email) || empty($newPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and new password are required']);
    exit;
}

$passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\_])[A-Za-z\d!@#$%^&*\_]{8,}$/';
if (!preg_match($passwordRegex, $newPassword)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'New password must be at least 8 characters long and contain uppercase, lowercase, numbers, and special characters (!@#$%^&*_)'
    ]);
    exit;
}

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashedPassword, $email);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
} else {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
}

$stmt->close();
$conn->close();
?>