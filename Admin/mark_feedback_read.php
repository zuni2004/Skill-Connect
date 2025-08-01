<?php
// mark_feedback_read.php

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

if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Feedback ID is required.']);
    exit();
}

$id = (int)$data['id'];

try {
    $sql = "UPDATE feedback SET read_by_admin = TRUE WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Feedback marked as read successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark feedback as read. Feedback not found or already marked as read.']);
    }

} catch (PDOException $e) {
    error_log("Error marking feedback as read: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to mark feedback as read. Database error.'
    ]);
}
?>
