<?php
// get_student_full_details.php
// This script fetches all details for a single student by their ID.
// It is used to populate both the 'Edit User' and 'View User Details' modals.

// Include the database connection file
require_once 'db_connect.php';

// Set the content type header to JSON
header('Content-Type: application/json');

// Get the student_id from the GET request
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;

if ($student_id) {
    try {
        // Prepare the SQL query to select all columns for a specific student ID
        // DO NOT select 'password' or 'photo' unless explicitly needed and handled securely
        $stmt = $pdo->prepare("SELECT student_id, first_name, last_name, username, email, 
                                     date_of_birth, cnic, age, phone, bio, academic_history, 
                                     country, province, city, area, street, postal_code, 
                                     agreed_terms, is_approved, approved_at, created_at 
                               FROM students 
                               WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        // Fetch the student's details
        $student = $stmt->fetch();

        if ($student) {
            // Return success with the student data
            echo json_encode(['success' => true, 'student' => $student]);
        } else {
            // Return failure if student not found
            echo json_encode(['success' => false, 'message' => 'Student not found.']);
        }

    } catch (PDOException $e) {
        // Return JSON error message on database error
        error_log("Error in get_student_full_details.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    // Return error if student_id is not provided
    echo json_encode(['success' => false, 'message' => 'Invalid request: student_id not provided.']);
}
?>
