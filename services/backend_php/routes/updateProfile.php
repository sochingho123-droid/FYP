<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

isLoggedIn(); 

$userId   = $_SESSION['userId'];
$input    = json_decode(file_get_contents('php://input'), true);
$newName  = trim($input['username'] ?? '');
$newEmail = trim($input['email'] ?? '');

if (empty($newName) || empty($newEmail)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and email cannot be empty']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
$stmt->bind_param("ssi", $newName, $newEmail, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User information updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

$stmt->close();
$conn->close();
?>