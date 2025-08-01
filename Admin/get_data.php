<?php
// get_data.php
// This script fetches all student records from the 'students' table
// and all records from the 'banned_users' table.
// It returns both sets of data as a single JSON object.

// Include the database connection file
require_once 'db_connect.php';

// Set the content type header to JSON
header('Content-Type: application/json');

try {
    // Fetch all students (active and pending)
    $stmt_students = $pdo->query("SELECT student_id, first_name, last_name, email, is_approved, created_at FROM students ORDER BY student_id ASC");
    $students = $stmt_students->fetchAll();

    // Fetch all banned users
    $stmt_banned = $pdo->query("SELECT banned_id, original_student_id, first_name, last_name, email, reason_for_ban, banned_at FROM banned_users ORDER BY banned_at DESC");
    $banned_users = $stmt_banned->fetchAll();
    
    // Return both arrays in a single JSON response
    echo json_encode(['success' => true, 'students' => $students, 'banned_users' => $banned_users]);
} catch (PDOException $e) {
    // If an error occurs, return a JSON error message
    error_log("Error in get_data.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
