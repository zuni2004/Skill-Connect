<?php
session_start();

// Only allow authenticated tutors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("HTTP/1.1 403 Forbidden");
    exit;
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
    die(json_encode(['error' => 'Database connection failed']));
}

$tutor_id = intval($_GET['tutor_id']);
$student_id = intval($_GET['student_id']);

// Get messages
$sql = "SELECT * FROM tutor_messages 
        WHERE tutor_id = ? AND student_id = ?
        ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $tutor_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Mark new messages as read
$sql = "UPDATE tutor_messages SET is_read = TRUE 
        WHERE tutor_id = ? AND student_id = ? AND sender = 'student'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $tutor_id, $student_id);
$stmt->execute();

echo json_encode($messages);
$conn->close();
?>