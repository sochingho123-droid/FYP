<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

isAdmin(); 

$currentAdminId = $_SESSION['userId'];
$targetUserId   = intval($_GET['id'] ?? $input['id'] ?? 0);

if ($currentAdminId === $targetUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Admin cannot delete themselves']);
    exit;
}

$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$targetUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$targetUser) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User does not exist']);
    exit;
}

if ($targetUser['role'] === 'admin') {
    $res = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    if ($res->fetch_assoc()['count'] <= 1) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete the last remaining admin. At least 1 admin is required.'
        ]);
        exit;
    }
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $targetUserId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $logStmt = $conn->prepare("INSERT INTO audit_logs (operator_id, action, target_user_id, ip_address) VALUES (?, 'DELETE_USER', ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $logStmt->bind_param("iis", $currentAdminId, $targetUserId, $ip);
    $logStmt->execute();
    $logStmt->close();

    echo json_encode(['success' => true, 'message' => 'User deleted successfully ']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

$stmt->close();
$conn->close();
?>