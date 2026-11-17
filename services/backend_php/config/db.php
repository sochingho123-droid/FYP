<?php


$host     = 'localhost'; 
$username = 'root';
$password = '';         
$dbname   = 'users';
$port     = 3306;


$conn = new mysqli($host, $username, $password, $dbname, $port);


if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

$adminCheck = $conn->query("SELECT id FROM users WHERE username = 'admin'");
if ($adminCheck && $adminCheck->num_rows === 0) {
    $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES ('admin', 'admin@example.com', ?, 'admin')");
    $stmt->bind_param("s", $hashedPassword);
    $stmt->execute();
    $stmt->close();
}

$conn->set_charset("utf8mb4");
?>