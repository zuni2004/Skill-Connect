<?php
// delete_employer.php

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

if (empty($data['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Employer ID is required.']);
    exit();
}

$id = (int)$data['id'];

try {
    $sql = "DELETE FROM employers WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Employer deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete employer. Employer not found.']);
    }

} catch (PDOException $e) {
    error_log("Error deleting employer: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete employer. Database error.'
    ]);
}
?>
