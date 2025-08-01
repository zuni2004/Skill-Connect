<?php
// toggle_employer_ban.php

require_once 'db_connect.php'; // Path adjusted for single-folder setup

header('Content-Type: application/json');

session_start(); // Start the session at the very beginning

// IMPORTANT: This is a TEMPORARY and INSECURE bypass for development.
/*
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login as admin.']);
    exit();
}
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Ensure 'id' is present and 'is_banned' is a boolean value (true/false)
if (empty($data['id']) || !isset($data['is_banned'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Employer ID and ban status are required.']);
    exit();
}

$id = (int)$data['id'];
$is_banned = (bool)$data['is_banned']; // Convert to boolean

try {
    $sql = "UPDATE employers SET is_banned = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$is_banned, $id]);

    if ($stmt->rowCount() > 0) {
        $action = $is_banned ? 'banned' : 'unbanned';
        echo json_encode(['success' => true, 'message' => "Employer $action successfully."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update ban status. Employer not found or status already set.']);
    }

} catch (PDOException $e) {
    error_log("Error toggling employer ban status: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Failed to toggle ban status. Database error.'
    ]);
}
?>
