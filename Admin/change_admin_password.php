<?php
// change_admin_password.php
require_once 'db_connect.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
if (empty($data['id']) || empty($data['current_password']) || empty($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

$admin_id = (int)$data['id'];
$current_password = $data['current_password'];
$new_password = $data['new_password'];

try {
    // 1. Get the admin's current password
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
        exit();
    }

    $stored_password = $admin['password'];

    // 2. Debug output - remove in production!
    error_log("Debug Info:");
    error_log("Admin ID: $admin_id");
    error_log("Input current password: '$current_password'");
    error_log("Stored password: '$stored_password'");
    error_log("Comparison result: " . ($current_password === $stored_password ? 'match' : 'no match'));

    // 3. Verify current password (plain text comparison)
    if ($current_password !== $stored_password) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect',
            'debug' => [
                'input_length' => strlen($current_password),
                'stored_length' => strlen($stored_password),
                'exact_match' => $current_password === $stored_password
            ]
        ]);
        exit();
    }

    // 4. Update to new password (stored in plain text)
    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $stmt->execute([$new_password, $admin_id]);

    if ($stmt->rowCount() === 1) {
        echo json_encode([
            'success' => true, 
            'message' => 'Password updated successfully',
            'note' => 'Password has been stored in plain text'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No changes made to password']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}