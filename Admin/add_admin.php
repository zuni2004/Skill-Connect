<?php
// add_admin.php

require_once 'db_connect.php'; // Path adjusted for single-folder setup

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Username, email, and password are required.']);
    exit();
}

$username = htmlspecialchars(trim($data['username']));
$email = htmlspecialchars(trim($data['email']));
$password = $data['password'];

try {
    // Check for duplicate username or email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO admins (username, email, password) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $email, $hashed_password]);

    echo json_encode(['success' => true, 'message' => 'Admin added successfully.']);

} catch (PDOException $e) {
    error_log("Error adding admin: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add admin. Please try again later.']);
}
?>