<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

isAdmin();

$result = $conn->query("SELECT id AS _id, username, email, role FROM users");
$users  = $result->fetch_all(MYSQLI_ASSOC);

foreach ($users as &$u) {
    $u['_id'] = (string)$u['_id'];
}

echo json_encode(['success' => true, 'users' => $users]);
$conn->close();
?>