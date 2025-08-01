<?php
// delete_admin.php

require_once 'db_connect.php'; // Path adjusted for single-folder setup

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Admin ID is required.']);
    exit();
}

$id = (int)$data['id'];

try {
    $sql = "DELETE FROM admins WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Admin deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Admin not found or could not be deleted.']);
    }

} catch (PDOException $e) {
    error_log("Error deleting admin: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete admin. Please try again later.']);
}
?>