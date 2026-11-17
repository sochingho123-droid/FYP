<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$username        = trim($input['username'] ?? '');
$password        = $input['password'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';
$email           = strtolower(trim($input['email'] ?? ''));

if (empty($username) || empty($password) || empty($confirmPassword) || empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username cannot be empty']);
    exit;
}

$passwordTest = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*\_]).{8,}$/';
if (!preg_match($passwordTest, $password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character']);
    exit;
}

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

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
$stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'user')");
$stmt->bind_param("sss", $username, $hashedPassword, $email);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    session_start();
    $_SESSION['userId'] = $newId;
    $_SESSION['role']   = 'user';

    echo json_encode([
        'success' => true,
        'message' => 'User created and logged in successfully',
        'user'    => [
            '_id'      => (string)$newId,
            'username' => $username,
            'email'    => $email,
            'role'     => 'user'
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $conn->error]);
}
$stmt->close();
$conn->close();
?>