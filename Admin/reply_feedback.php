<?php
// reply_feedback.php

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
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['id']) || !isset($data['reply'])) { // 'reply' can be empty if clearing it
    echo json_encode(['success' => false, 'message' => 'Feedback ID and reply are required.']);
    exit();
}

$id = (int)$data['id'];
$reply = trim($data['reply']);
$replied_at = !empty($reply) ? date('Y-m-d H:i:s') : null; // Set timestamp if reply is not empty

try {
    $sql = "UPDATE feedback SET reply = ?, replied_at = ?, read_by_admin = TRUE WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reply, $replied_at, $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Feedback reply sent successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send reply. Feedback not found or no changes made.']);
    }

} catch (PDOException $e) {
    error_log("Error sending feedback reply: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send feedback reply. Database error.'
    ]);
}
?>
