<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

isLoggedIn(); 

$userId = $_SESSION['userId'];

$input           = json_decode(file_get_contents('php://input'), true);
$currentPassword = $input['password'] ?? '';
$newPassword     = $input['newPassword'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Current and new passwords are required']);
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

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || (!password_verify($currentPassword, $user['password']) && $currentPassword !== $user['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$updateStmt->bind_param("si", $hashedPassword, $userId);
$updateStmt->execute();

echo json_encode(['success' => true, 'message' => 'Password changed successfully']);

$stmt->close();
$updateStmt->close();
$conn->close();
?>