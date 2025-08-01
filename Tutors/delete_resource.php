<?php
session_start();

// Verify user is logged in as tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(['success' => false, 'message' => 'Access denied']));
}

// Database configuration
$host = "sql12.freesqldatabase.com";
$port = "3306";
$db = "sql12784403";
$user = "sql12784403";
$pass = "WAuJFq9xaX";

// Create connection
$conn = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get resource ID
$resource_id = intval($_POST['resource_id']);
$tutor_id = $_SESSION['user_id'];

// First get file path
$sql = "SELECT file_path FROM tutor_resources WHERE resource_id = ? AND tutor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $resource_id, $tutor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Resource not found']);
    exit;
}

$resource = $result->fetch_assoc();
$file_path = $resource['file_path'];

// Delete from database
$sql = "DELETE FROM tutor_resources WHERE resource_id = ? AND tutor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $resource_id, $tutor_id);

$response = [];
if ($stmt->execute()) {
    // Delete the actual file
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $response = ['success' => true];
} else {
    $response = ['success' => false, 'message' => 'Database error'];
}

$conn->close();
echo json_encode($response);
?>