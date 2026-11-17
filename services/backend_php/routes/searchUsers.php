<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

isAdmin(); 

$input   = json_decode(file_get_contents('php://input'), true);
$keyword = trim($input['keyword'] ?? '');

if (empty($keyword)) {
    $result = $conn->query("SELECT id AS _id, username, email, role FROM users");
    $users  = $result->fetch_all(MYSQLI_ASSOC);
} else if (is_numeric($keyword)) {
    $stmt = $conn->prepare("SELECT id AS _id, username, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $keyword);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $search = "%{$keyword}%";
    $stmt = $conn->prepare("SELECT id AS _id, username, email, role FROM users WHERE username LIKE ? OR email LIKE ?");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

foreach ($users as &$u) {
    $u['_id'] = (string)$u['_id'];
}

echo json_encode(['success' => true, 'users' => $users]);
$conn->close();
?>